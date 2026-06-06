<?php

namespace Tests\Feature\Public\Reseller;

use App\Models\User;
use App\Models\ResellerIntegration;
use App\Models\ResellerCallbackProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CredentialControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create a reseller user that passes the reseller.only middleware.
     * The middleware checks: $user->resellerIntegrations()->exists()
     * So we must create at least one integration.
     */
    private function createResellerWithIntegration(string $mode = 'live'): array
    {
        $user = User::factory()->create(['role' => 'Member']);

        $integration = ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-' . strtoupper($mode) . '-01',
            'mode'             => $mode,
            'is_active'        => true,
        ]);

        return [$user, $integration];
    }

    public function test_reseller_can_view_credentials_page(): void
    {
        [$user] = $this->createResellerWithIntegration();

        $response = $this->actingAs($user)->get('/id/reseller/credentials');

        // Assert 200 only — Inertia component assertion is skipped because React
        // build assets are not present in the test environment.
        $response->assertStatus(200);
    }

    public function test_reseller_can_update_webhook_url(): void
    {
        [$user, $integration] = $this->createResellerWithIntegration('live');

        $response = $this->actingAs($user)->post('/id/reseller/credentials/webhook', [
            'mode'            => 'live',
            'url'             => 'https://my-webhook.com/api/callback',
            'generate_secret' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('flash_success');

        $this->assertDatabaseHas('reseller_callback_profiles', [
            'reseller_integration_id' => $integration->id,
            'callback_url'            => 'https://my-webhook.com/api/callback',
        ]);
    }

    public function test_reseller_cannot_update_invalid_webhook_url(): void
    {
        [$user] = $this->createResellerWithIntegration('live');

        // The controller validates 'url' with 'required|url'
        // localhost is a valid URL per RFC but the app may also accept it at validation level
        // Testing with a completely invalid string instead
        $response = $this->actingAs($user)->post('/id/reseller/credentials/webhook', [
            'mode' => 'live',
            'url'  => 'not-a-valid-url',
        ]);

        $response->assertSessionHasErrors(['url']);
    }

    public function test_reseller_can_generate_webhook_secret(): void
    {
        [$user, $integration] = $this->createResellerWithIntegration('live');

        // Pre-create a callback profile with a URL (required to generate secret)
        $profile = ResellerCallbackProfile::create([
            'reseller_integration_id' => $integration->id,
            'callback_url'            => 'https://my-webhook.com/api',
            'is_enabled'              => true,
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/credentials/webhook', [
            'mode'            => 'live',
            'url'             => 'https://my-webhook.com/api',
            'generate_secret' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('new_webhook_secret');
        $response->assertSessionHas('webhook_mode', 'live');

        $profile->refresh();
        $this->assertNotEmpty($profile->decryptedWebhookSecret());
    }
}
