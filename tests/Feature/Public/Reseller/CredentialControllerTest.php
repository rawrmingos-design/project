<?php

namespace Tests\Feature\Public\Reseller;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CredentialControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser(bool $with2fa = true): User
    {
        $user = User::factory()->create([
            'role'              => 'Member',
            'two_factor_secret' => $with2fa ? 'JBSWY3DPEHPK3PXP' : null,
        ]);

        return $user;
    }

    public function test_reseller_can_view_credentials_page(): void
    {
        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->get('/id/reseller/credentials');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Public/Pages/Reseller/Credentials'));
    }

    public function test_reseller_can_update_webhook_url(): void
    {
        $user = $this->createResellerUser();

        // Ensure no integration exists yet
        $this->assertDatabaseMissing('reseller_integrations', ['user_id' => $user->id]);

        $response = $this->actingAs($user)->post('/id/reseller/credentials/update-url', [
            'callback_url' => 'https://my-webhook.com/api/callback',
        ]);

        $response->assertRedirect('/id/reseller/credentials');
        $response->assertSessionHas('success', 'URL Webhook berhasil diperbarui!');

        $this->assertDatabaseHas('reseller_integrations', [
            'user_id'      => $user->id,
            'callback_url' => 'https://my-webhook.com/api/callback',
        ]);
    }

    public function test_reseller_cannot_update_invalid_webhook_url(): void
    {
        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->post('/id/reseller/credentials/update-url', [
            'callback_url' => 'http://localhost/api/callback', // Invalid (localhost)
        ]);

        $response->assertSessionHasErrors(['callback_url']);

        $this->assertDatabaseMissing('reseller_integrations', ['user_id' => $user->id]);
    }

    public function test_reseller_can_generate_webhook_secret(): void
    {
        $user = $this->createResellerUser(with2fa: true); // Must have 2FA

        // Create integration with callback url
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-01',
            'is_active'        => true,
            'mode'             => 'live',
            'callback_url'     => 'https://my-webhook.com/api',
            'api_key'          => null,
            'api_key_hint'     => null,
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/credentials/generate-secret');

        $response->assertRedirect('/id/reseller/credentials');
        $response->assertSessionHas('success', 'Webhook Secret berhasil di-generate.');
        $response->assertSessionHas('new_webhook_secret'); // Should flash the plain secret

        $integration = ResellerIntegration::where('user_id', $user->id)->first();
        
        $this->assertNotNull($integration->api_key);
        $this->assertNotNull($integration->api_key_hint);
        $this->assertTrue(Hash::check(session('new_webhook_secret'), $integration->api_key));
    }

    public function test_reseller_cannot_generate_secret_without_url(): void
    {
        $user = $this->createResellerUser(with2fa: true);

        // Create integration WITHOUT callback url
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-01',
            'is_active'        => true,
            'mode'             => 'live',
            'callback_url'     => null,
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/credentials/generate-secret');

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Silakan atur URL Webhook terlebih dahulu.');

        $integration = ResellerIntegration::where('user_id', $user->id)->first();
        $this->assertNull($integration->api_key);
    }

    public function test_reseller_cannot_generate_secret_without_2fa(): void
    {
        $user = $this->createResellerUser(with2fa: false); // No 2FA

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-01',
            'is_active'        => true,
            'mode'             => 'live',
            'callback_url'     => 'https://my-webhook.com/api',
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/credentials/generate-secret');

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Anda harus mengaktifkan Two-Factor Authentication (2FA) sebelum membuat Webhook Secret.');
    }
}
