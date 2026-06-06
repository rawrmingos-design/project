<?php

namespace Tests\Feature\Public\Reseller;

use App\Models\User;
use App\Models\ResellerIntegration;
use App\Models\ResellerCallbackProfile;
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
        $response->assertInertia(fn (Assert $page) => $page->component('Reseller/Credentials'));
    }

    public function test_reseller_can_update_webhook_url(): void
    {
        $user = $this->createResellerUser();

        $integration = ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-01',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/credentials/webhook', [
            'mode' => 'live',
            'url' => 'https://my-webhook.com/api/callback',
            'generate_secret' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success', 'Webhook URL live berhasil diperbarui.');

        $this->assertDatabaseHas('reseller_callback_profiles', [
            'reseller_integration_id' => $integration->id,
            'callback_url' => 'https://my-webhook.com/api/callback',
        ]);
    }

    public function test_reseller_cannot_update_invalid_webhook_url(): void
    {
        $user = $this->createResellerUser();

        $response = $this->actingAs($user)->post('/id/reseller/credentials/webhook', [
            'mode' => 'live',
            'url' => 'http://localhost/api/callback', // Invalid (localhost)
        ]);

        $response->assertSessionHasErrors(['url']);
    }

    public function test_reseller_can_generate_webhook_secret(): void
    {
        $user = $this->createResellerUser(with2fa: true); // Must have 2FA

        $integration = ResellerIntegration::create([
            'user_id' => $user->id,
            'integration_code' => 'TEST-01',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $profile = ResellerCallbackProfile::create([
            'reseller_integration_id' => $integration->id,
            'callback_url' => 'https://my-webhook.com/api',
            'is_enabled' => true,
            'webhook_secret' => null, // Initially null
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/credentials/webhook', [
            'mode' => 'live',
            'url' => 'https://my-webhook.com/api',
            'generate_secret' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('new_webhook_secret'); // Should flash the plain secret
        $response->assertSessionHas('webhook_mode', 'live');

        $profile->refresh();
        $this->assertNotEmpty($profile->decryptedWebhookSecret());
    }
}
