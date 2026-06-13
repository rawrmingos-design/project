<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\ResellerApplicationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ResellerApplicationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable captcha for validation tests
        \DB::table('setting_webs')->updateOrInsert(
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
                'topupindo_api' => 'test',
                'warna1' => '#000',
                'warna2' => '#111',
                'warna3' => '#222',
                'warna4' => '#333',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TST',
                'captcha_bypass' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function test_requires_business_name()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $request = new ResellerApplicationRequest();
        $validator = Validator::make([], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('business_name', $validator->errors()->toArray());
    }
    
    public function test_captcha_bypassed_when_configured()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $request = new ResellerApplicationRequest();
        $rules = $request->rules();
        
        // Captcha should be bypassed (empty array = no validation)
        $this->assertIsArray($rules['g-recaptcha-response']);
        $this->assertEmpty($rules['g-recaptcha-response']);
    }
    
    public function test_validates_business_url_format()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $request = new ResellerApplicationRequest();
        $validator = Validator::make([
            'business_name' => 'Test Business',
            'business_url' => 'not-a-valid-url',
            'g-recaptcha-response' => 'test-token',
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('business_url', $validator->errors()->toArray());
    }
    
    public function test_requires_documents_on_first_submit()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $request = new ResellerApplicationRequest();
        $validator = Validator::make([
            'business_name' => 'Test Business',
            'g-recaptcha-response' => 'test-token',
        ], $request->rules());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('identity', $validator->errors()->toArray());
        $this->assertArrayHasKey('selfie', $validator->errors()->toArray());
        $this->assertArrayHasKey('business_proof', $validator->errors()->toArray());
    }
    
    public function test_validates_file_mime_types()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $request = new ResellerApplicationRequest();
        $rules = $request->rules();
        
        // Check identity mimes rule includes jpg, jpeg, png, pdf
        $identityMimes = collect($rules['identity'])->first(fn($rule) => str_starts_with($rule, 'mimes:'));
        $this->assertStringContainsString('jpg', $identityMimes);
        $this->assertStringContainsString('jpeg', $identityMimes);
        $this->assertStringContainsString('png', $identityMimes);
        $this->assertStringContainsString('pdf', $identityMimes);
        
        // Check selfie mimes includes jpg, png
        $selfieMimes = collect($rules['selfie'])->first(fn($rule) => str_starts_with($rule, 'mimes:'));
        $this->assertStringContainsString('jpg', $selfieMimes);
        $this->assertStringContainsString('png', $selfieMimes);
    }
    
    public function test_has_custom_error_messages()
    {
        $request = new ResellerApplicationRequest();
        $messages = $request->messages();
        
        $this->assertArrayHasKey('business_name.required', $messages);
        $this->assertArrayHasKey('g-recaptcha-response.required', $messages);
        $this->assertArrayHasKey('g-recaptcha-response.captcha', $messages);
        
        $this->assertEquals('Captcha wajib diverifikasi.', $messages['g-recaptcha-response.required']);
        $this->assertEquals('Verifikasi captcha gagal. Silakan coba lagi.', $messages['g-recaptcha-response.captcha']);
    }
    
    public function test_authorize_returns_true_for_authenticated_user()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $request = new ResellerApplicationRequest();
        $request->setUserResolver(fn() => $user);
        
        $this->assertTrue($request->authorize());
    }
    
    public function test_authorize_returns_false_for_guest()
    {
        $request = new ResellerApplicationRequest();
        $request->setUserResolver(fn() => null);
        
        $this->assertFalse($request->authorize());
    }
}
