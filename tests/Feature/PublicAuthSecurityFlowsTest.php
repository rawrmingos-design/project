<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class PublicAuthSecurityFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('ADMIN_LOGIN_CAPTCHA_ENABLED=false');
        $_ENV['ADMIN_LOGIN_CAPTCHA_ENABLED'] = 'false';
        $_SERVER['ADMIN_LOGIN_CAPTCHA_ENABLED'] = 'false';
    }

    public function test_google_login_creates_member_when_account_does_not_exist(): void
    {
        config()->set('services.google.client_id', 'google-client-demo');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'google-client-demo',
                'iss' => 'https://accounts.google.com',
                'sub' => 'google-sub-1001',
                'email' => 'new-user@example.com',
                'email_verified' => 'true',
                'name' => 'New User',
                'picture' => 'https://cdn.example.com/avatar.webp',
            ], 200),
        ]);

        $response = $this->post('/id/auth/google', [
            'credential' => 'mock-credential-token',
        ]);

        $response->assertRedirect('/id/dashboard');
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'new-user@example.com',
            'role' => 'Member',
            'google_id' => 'google-sub-1001',
        ]);
    }

    public function test_google_login_links_existing_user_by_email(): void
    {
        config()->set('services.google.client_id', 'google-client-demo');

        $user = User::factory()->create([
            'email' => 'member-linked@example.com',
            'username' => 'member-linked',
            'google_id' => null,
            'google_avatar' => null,
            'role' => 'Member',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'google-client-demo',
                'iss' => 'accounts.google.com',
                'sub' => 'google-sub-2002',
                'email' => 'member-linked@example.com',
                'email_verified' => 'true',
                'name' => 'Member Linked',
                'picture' => 'https://cdn.example.com/new-avatar.webp',
            ], 200),
        ]);

        $response = $this->post('/id/auth/google', [
            'credential' => 'mock-credential-token',
        ]);

        $response->assertRedirect('/id/dashboard');
        $this->assertAuthenticatedAs($user->fresh());

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'google_id' => 'google-sub-2002',
            'google_avatar' => 'https://cdn.example.com/new-avatar.webp',
        ]);
    }

    public function test_google_login_rejects_invalid_token_payload(): void
    {
        config()->set('services.google.client_id', 'google-client-demo');

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'aud' => 'wrong-client-id',
                'iss' => 'https://accounts.google.com',
                'sub' => 'google-sub-invalid',
                'email' => 'invalid@example.com',
                'email_verified' => 'true',
            ], 200),
        ]);

        $response = $this
            ->from('/id/sign-in')
            ->post('/id/auth/google', [
                'credential' => 'mock-credential-token',
            ]);

        $response->assertRedirect('/id/sign-in');
        $response->assertSessionHasErrors('error');
        $this->assertGuest();
    }

    public function test_two_factor_can_be_enabled_and_enforced_on_username_password_login(): void
    {
        $user = User::factory()->create([
            'username' => 'twofactoruser',
            'password' => Hash::make('password'),
            'role' => 'Member',
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $setupResponse = $this
            ->actingAs($user)
            ->postJson('/id/settings/2fa/setup');

        $setupResponse->assertOk()->assertJsonPath('status', 'success');
        $this->assertStringStartsWith('data:image/', (string) $setupResponse->json('data.qr_image_url'));

        $secret = session('settings_2fa_pending_secret');
        $this->assertNotEmpty($secret);

        $google2fa = new Google2FA();
        $enableCode = $google2fa->getCurrentOtp($secret);

        $enableResponse = $this
            ->actingAs($user)
            ->postJson('/id/settings/2fa/enable', [
                'code' => $enableCode,
            ]);

        $enableResponse->assertOk()->assertJsonPath('status', 'success');
        $user->refresh();
        $this->assertNotEmpty($user->two_factor_secret);

        $this->post('/id/logout')->assertRedirect('/');
        $this->assertGuest();

        $failedLoginResponse = $this->from('/id/sign-in')->post('/id/sign-in', [
            'username' => 'twofactoruser',
            'password' => 'password',
        ]);

        $failedLoginResponse->assertRedirect('/id/sign-in');
        $failedLoginResponse->assertSessionHasErrors('two_factor_code');
        $this->assertGuest();

        $validLoginCode = $google2fa->getCurrentOtp((string) $user->two_factor_secret);

        $successfulLoginResponse = $this->post('/id/sign-in', [
            'username' => 'twofactoruser',
            'password' => 'password',
            'two_factor_code' => $validLoginCode,
        ]);

        $successfulLoginResponse->assertRedirect('/id/dashboard');
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_authenticated_user_can_regenerate_api_key_from_settings(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'api_key' => 'old-api-key-123',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/id/settings/api-key/regenerate');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNotSame('old-api-key-123', $user->api_key);
        $this->assertSame(32, strlen((string) $user->api_key));
    }
}
