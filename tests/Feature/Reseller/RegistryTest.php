<?php

namespace Tests\Feature\Reseller;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        
        // Keep the default registry fixture explicitly disabled until a test opts in.
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'Test',
                'deskripsi_web' => 'Test',
                'keywords' => 'test',
                'url_wa' => 'https://wa.me/test',
                'url_ig' => 'https://instagram.com/test',
                'url_tiktok' => 'https://tiktok.com/@test',
                'url_youtube' => 'https://youtube.com/@test',
                'url_fb' => 'https://facebook.com/test',
                'warna1' => '#000',
                'warna2' => '#111',
                'warna3' => '#222',
                'warna4' => '#333',
                'topupindo_api' => 'test',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TST',
                'captcha_enabled' => false,
                'captcha_bypass' => true,
                'captcha_site_key' => null,
                'captcha_secret' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Config::set('captcha.sitekey', '');
        Config::set('captcha.secret', '');
    }

    public function test_authenticated_user_can_access_registry_form()
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Member']);
        
        $response = $this->actingAs($user)->get(route('reseller.registry.form'));
        
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Reseller/Registry')
            ->has('current_user', fn ($user_data) => $user_data
                ->has('username')
                ->has('email')
                ->has('role')
                ->has('phone')
            )
            ->has('captcha', fn ($captcha) => $captcha
                ->has('site_key')
                ->has('bypass')
                ->has('misconfigured')
                ->where('enabled', false)
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

    public function test_registry_form_uses_captcha_enabled_flag()
    {
        DB::table('setting_webs')->where('id', 1)->update([
            'captcha_enabled' => false,
            'captcha_bypass' => false,
            'captcha_site_key' => 'site-key',
            'captcha_secret' => 'secret',
        ]);

        Config::set('captcha.sitekey', 'site-key');
        Config::set('captcha.secret', 'secret');

        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->get(route('reseller.registry.form'));

        $response->assertInertia(fn ($page) => $page
            ->where('captcha.enabled', false)
            ->where('captcha.misconfigured', false)
        );
    }

    public function test_registry_form_marks_enabled_captcha_without_credentials_as_misconfigured()
    {
        Config::set('captcha.sitekey', '');
        Config::set('captcha.secret', '');

        DB::table('setting_webs')->where('id', 1)->update([
            'captcha_enabled' => true,
            'captcha_bypass' => false,
            'captcha_site_key' => null,
            'captcha_secret' => null,
        ]);

        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->get(route('reseller.registry.form'));

        $response->assertInertia(fn ($page) => $page
            ->where('captcha.enabled', false)
            ->where('captcha.misconfigured', true)
        );
    }

    public function test_registry_form_bypass_does_not_render_captcha()
    {
        DB::table('setting_webs')->where('id', 1)->update([
            'captcha_enabled' => true,
            'captcha_bypass' => true,
            'captcha_site_key' => 'site-key',
            'captcha_secret' => 'secret',
        ]);

        Config::set('captcha.sitekey', 'site-key');
        Config::set('captcha.secret', 'secret');

        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Member']);

        $response = $this->actingAs($user)->get(route('reseller.registry.form'));

        $response->assertInertia(fn ($page) => $page
            ->where('captcha.enabled', false)
            ->where('captcha.bypass', true)
            ->where('captcha.misconfigured', false)
        );
    }

    public function test_submit_requires_authentication()
    {
        $response = $this->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
        ]);
        
        $response->assertRedirect(route('login'));
    }

    public function test_submit_requires_business_name()
    {
        /** @var User&Authenticatable $user */
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
        /** @var User&Authenticatable $user */
        $user = User::factory()->create(['role' => 'Member']);
        
        $response = $this->actingAs($user)->post(route('reseller.registry.submit'), [
            'business_name' => 'Test Business',
            'g-recaptcha-response' => 'test-token',
        ]);
        
        $response->assertSessionHasErrors(['identity', 'selfie', 'business_proof']);
    }

    public function test_rate_limiting_blocks_excessive_submissions()
    {
        /** @var User&Authenticatable $user */
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
        /** @var User&Authenticatable $user */
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
        /** @var User&Authenticatable $user */
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
        /** @var User&Authenticatable $user */
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
