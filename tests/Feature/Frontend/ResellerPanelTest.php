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
        $response = $this->get('/id/reseller/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_non_reseller_member_is_redirected_away(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/dashboard');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Akses ditolak: Anda tidak memiliki integrasi Reseller.');
    }

    public function test_reseller_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        ResellerIntegration::factory()->create([
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

        $response = $this->actingAs($admin)->get('/id/reseller/dashboard');

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error', 'Akses ditolak: Anda tidak memiliki integrasi Reseller.');
    }

    public function test_credentials_props_do_not_expose_raw_api_key_and_webhook_secret(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        $integration = ResellerIntegration::factory()->create([
            'user_id' => $user->id,
            'integration_code' => 'live-integration-test',
            'mode' => 'live',
            'is_active' => true,
            'api_key_hash' => \Illuminate\Support\Facades\Hash::make('secret-live-key-123456'),
            'api_key_hint' => '...123456',
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

    public function test_reseller_docs_page_returns_404_when_canonical_docs_are_unavailable(): void
    {
        $user = User::factory()->create([
            'role' => 'Member',
        ]);

        ResellerIntegration::factory()->create([
            'user_id' => $user->id,
            'integration_code' => 'sandbox-integration-test',
            'mode' => 'live',
            'is_active' => true,
            'api_key_hint' => '...sandbox',
            'api_key_hash' => \Illuminate\Support\Facades\Hash::make('secret-live-key-123456'),
        ]);

        $response = $this->actingAs($user)->get('/id/reseller/docs');

        if (\Illuminate\Support\Facades\Route::has('docs.index')) {
            $response->assertRedirect(route('docs.index'));
            $this->assertSame(301, $response->getStatusCode());
        } else {
            $response->assertNotFound();
        }

        $response->assertDontSee('secret-live-key-123456');
        $response->assertDontSee('sandbox-integration-test');
    }

    public function test_orders_page_only_shows_authenticated_reseller_orders(): void
    {
        $user1 = User::factory()->create(['role' => 'Member']);
        $user2 = User::factory()->create(['role' => 'Member']);

        $integration1 = ResellerIntegration::factory()->create(['user_id' => $user1->id, 'integration_code' => 'int-1', 'mode' => 'live', 'is_active' => true]);
        $integration2 = ResellerIntegration::factory()->create(['user_id' => $user2->id, 'integration_code' => 'int-2', 'mode' => 'live', 'is_active' => true]);

        $order1 = \App\Models\Pembelian::query()->create([
            'user_id' => $user1->id,
            'username' => $user1->username,
            'order_id' => 'INV-USER1-TEST',
            'reseller_integration_id' => $integration1->id,
            'harga' => 1000,
            'profit' => 100,
            'layanan' => 'Test Product',
            'status' => 'Pending',
        ]);

        $order2 = \App\Models\Pembelian::query()->create([
            'user_id' => $user2->id,
            'username' => $user2->username,
            'order_id' => 'INV-USER2-TEST',
            'reseller_integration_id' => $integration2->id,
            'harga' => 1000,
            'profit' => 100,
            'layanan' => 'Test Product',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($user1)->get('/id/reseller/orders');
        
        $response->assertOk();
        $response->assertSee('INV-USER1-TEST');
        $response->assertDontSee('INV-USER2-TEST');
    }

    public function test_callback_logs_only_show_authenticated_reseller_deliveries(): void
    {
        $user1 = User::factory()->create(['role' => 'Member']);
        $user2 = User::factory()->create(['role' => 'Member']);

        $integration1 = ResellerIntegration::factory()->create(['user_id' => $user1->id, 'integration_code' => 'int-1', 'mode' => 'live', 'is_active' => true]);
        $integration2 = ResellerIntegration::factory()->create(['user_id' => $user2->id, 'integration_code' => 'int-2', 'mode' => 'live', 'is_active' => true]);

        $profile1 = \App\Models\ResellerCallbackProfile::query()->create(['reseller_integration_id' => $integration1->id, 'callback_url' => 'http://1']);
        $profile2 = \App\Models\ResellerCallbackProfile::query()->create(['reseller_integration_id' => $integration2->id, 'callback_url' => 'http://2']);

        \App\Models\ResellerCallbackDelivery::query()->create([
            'reseller_integration_id' => $integration1->id,
            'reseller_callback_profile_id' => $profile1->id,
            'event_name' => 'order.status_changed',
            'callback_url' => 'http://1',
            'payload' => ['user' => 1],
            'status' => 'success',
        ]);

        \App\Models\ResellerCallbackDelivery::query()->create([
            'reseller_integration_id' => $integration2->id,
            'reseller_callback_profile_id' => $profile2->id,
            'event_name' => 'order.status_changed',
            'callback_url' => 'http://2',
            'payload' => ['user' => 2],
            'status' => 'failed',
            'last_response_status' => 500,
        ]);

        $response = $this->actingAs($user1)->get('/id/reseller/callbacks');
        
        $response->assertOk();
        $response->assertSee('"status":"success"', false); // User 1 has success
        $response->assertDontSee('"last_response_status":500', false); // User 2's error status shouldn't appear
    }
}
