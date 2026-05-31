<?php

namespace Tests\Feature\Frontend;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_panel(): void
    {
        $response = $this->get('/id/reseller');
        $response->assertRedirect(route('login'));
    }

    public function test_non_reseller_member_is_redirected_away(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        $response = $this->actingAs($user)->get('/id/reseller');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Akses ditolak: Anda tidak memiliki integrasi Reseller.');
    }

    public function test_reseller_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        ResellerIntegration::query()->create([
            'user_id' => $user->id,
            'integration_code' => 'test-integration',
            'mode' => 'sandbox',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/id/reseller');

        $response->assertOk();
        // Since it's Inertia, we can just assert it's a 200 OK. 
        // Inertia testing package assertions could be used here if needed.
    }

    public function test_admin_without_integration_cannot_access_reseller_panel(): void
    {
        $admin = User::factory()->create([
            'role' => 'Admin',
        ]);

        $response = $this->actingAs($admin)->get('/id/reseller');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Akses ditolak: Anda tidak memiliki integrasi Reseller.');
    }

    public function test_credentials_props_do_not_expose_raw_api_key_and_webhook_secret(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'api_key' => 'secret-live-key-123456',
        ]);

        $integration = ResellerIntegration::query()->create([
            'user_id' => $user->id,
            'integration_code' => 'live-integration-test',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $profile = \App\Models\ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->id,
            'is_enabled' => true,
            'callback_url' => 'https://example.com/webhook',
            'webhook_secret' => 'super-secret-webhook-key',
            'signing_algorithm' => 'sha256',
            'signature_header' => 'X-Signature',
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/credentials');
        
        $response->assertOk();
        
        // Assert the raw keys are NOT in the response content at all
        $response->assertDontSee('secret-live-key-123456');
        $response->assertDontSee('super-secret-webhook-key');

        // Assert the hints are visible
        $response->assertSee('...123456');
        $response->assertSee('example.com');
    }

    public function test_reseller_docs_page_does_not_expose_raw_secrets(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
            'sandbox_api_key_hint' => '...sandbox',
            'api_key' => 'secret-live-key-123456',
        ]);

        ResellerIntegration::query()->create([
            'user_id' => $user->id,
            'integration_code' => 'sandbox-integration-test',
            'mode' => 'sandbox',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/docs');
        
        $response->assertOk();
        $response->assertDontSee('secret-live-key-123456');
        $response->assertSee('...sandbox');
        $response->assertSee('sandbox-integration-test');
    }
}
