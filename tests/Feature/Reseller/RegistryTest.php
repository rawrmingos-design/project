<?php

namespace Tests\Feature\Reseller;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Mock captcha validation to always pass in tests
        Config::set('captcha.secret', 'test-secret-key');
    }

    public function test_guest_cannot_access_registry_form()
    {
        $response = $this->get(route('reseller.registry.form'));
        
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('warning', 'Silakan login sebagai Member untuk mendaftar sebagai reseller');
    }

    public function test_authenticated_user_can_access_registry_form()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        $response = $this->actingAs($user)->get(route('reseller.registry.form'));
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reseller/Registry')
            ->has('current_user', fn ($user_data) => $user_data
                ->has('username')
                ->has('email')
                ->has('role')
            )
            ->has('captcha', fn ($captcha) => $captcha
                ->has('site_key')
                ->where('enabled', true)
            )
        );
    }

    public function test_registry_form_displays_user_account_info()
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'username' => 'testuser',
            'email' => 'test@example.com',
        ]);
        
        $response = $this->actingAs($user)->get(route('reseller.registry.form'));
        
        $response->assertInertia(fn ($page) => $page
            ->where('current_user.username', 'testuser')
            ->where('current_user.email', 'test@example.com')
            ->where('current_user.role', 'Member')
        );
    }

    public function test_submit_requires_authentication()
    {
        $response = $this->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_submit_requires_captcha()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
            'business_url' => 'https://test.com',
            'identity' => UploadedFile::fake()->image('identity.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'business_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        
        $response->assertSessionHasErrors('g-recaptcha-response');
    }

    public function test_submit_requires_business_name()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'g-recaptcha-response' => 'test-token',
            'identity' => UploadedFile::fake()->image('identity.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'business_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        
        $response->assertSessionHasErrors('business_name');
    }

    public function test_submit_requires_all_documents()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
            'g-recaptcha-response' => 'test-token',
        ]);
        
        $response->assertSessionHasErrors(['identity', 'selfie', 'business_proof']);
    }

    public function test_rate_limiting_blocks_excessive_submissions()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        // Mock captcha to always pass
        $this->mockCaptchaValidation();
        
        // Make 5 requests (should succeed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
                'business_name' => 'Test Business ' . $i,
                'g-recaptcha-response' => 'test-token-' . $i,
                'identity' => UploadedFile::fake()->image('identity.jpg'),
                'selfie' => UploadedFile::fake()->image('selfie.jpg'),
                'business_proof' => UploadedFile::fake()->image('proof.jpg'),
            ]);
        }
        
        // 6th request should be rate limited
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business 6',
            'g-recaptcha-response' => 'test-token-6',
            'identity' => UploadedFile::fake()->image('identity.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'business_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        
        $response->assertStatus(429); // Too Many Requests
    }

    public function test_validates_business_url_format()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
            'business_url' => 'not-a-valid-url',
            'g-recaptcha-response' => 'test-token',
            'identity' => UploadedFile::fake()->image('identity.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'business_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        
        $response->assertSessionHasErrors('business_url');
    }

    public function test_validates_file_types()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        // Try uploading a non-image file for selfie
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
            'g-recaptcha-response' => 'test-token',
            'identity' => UploadedFile::fake()->image('identity.jpg'),
            'selfie' => UploadedFile::fake()->create('document.pdf', 100),
            'business_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        
        $response->assertSessionHasErrors('selfie');
    }

    public function test_validates_file_size_limit()
    {
        $user = User::factory()->create(['role' => 'Member']);
        
        // Try uploading file larger than 5MB
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
            'g-recaptcha-response' => 'test-token',
            'identity' => UploadedFile::fake()->create('identity.jpg', 6000), // 6MB
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
            'business_proof' => UploadedFile::fake()->image('proof.jpg'),
        ]);
        
        $response->assertSessionHasErrors('identity');
    }

    protected function mockCaptchaValidation()
    {
        // Register a mock captcha validator that always passes
        \Illuminate\Support\Facades\Validator::extend('captcha', function () {
            return true;
        });
    }
}
