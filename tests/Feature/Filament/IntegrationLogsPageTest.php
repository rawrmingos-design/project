<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Clusters\Integrations;
use App\Filament\Admin\Pages\IntegrationLogs;
use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use App\Models\InboundSourceEvent;
use App\Models\ResellerCallbackDelivery;
use App\Models\ResellerCallbackProfile;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\AdminTestCase;

class IntegrationLogsPageTest extends AdminTestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_integration_logs_page_and_see_incoming_and_outgoing_data(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $member = User::factory()->create(['role' => 'Member']);

        $integration = ResellerIntegration::query()->create([
            'user_id' => $member->getKey(),
            'integration_code' => 'logs-live-001',
            'mode' => 'live',
            'is_active' => true,
        ]);

        $profile = ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->getKey(),
            'is_enabled' => true,
            'callback_url' => 'https://client.example/callback',
            'webhook_secret' => 'logs-secret',
            'signing_algorithm' => 'sha256',
            'signature_header' => 'X-Callback-Signature',
            'version' => 1,
        ]);

        InboundSourceEvent::query()->create([
            'source_domain' => 'payment_gateway',
            'source_name' => 'tripay',
            'route_uri' => 'wejizy/tripay/callback',
            'method' => 'POST',
            'resolved_client_ip' => '95.111.200.230',
            'normalized_client_ip' => '95.111.200.230',
            'mode' => 'enforce',
            'decision' => 'allow',
            'reason' => 'matched',
        ]);

        ResellerCallbackDelivery::query()->create([
            'user_id' => $member->getKey(),
            'reseller_integration_id' => $integration->getKey(),
            'reseller_callback_profile_id' => $profile->getKey(),
            'environment' => 'live',
            'event_name' => 'h2h.order.updated',
            'order_id' => 'WEJIZY-RAPI123',
            'reference_number' => 'EXT-REF-LOGS-001',
            'callback_url' => 'https://client.example/callback',
            'signature_algorithm' => 'sha256',
            'payload' => ['event' => 'h2h.order.updated'],
            'attempt_count' => 1,
            'status' => 'delivered',
            'last_attempted_at' => now(),
            'last_response_status' => 200,
        ]);

        $this->actingAs($admin);

        $this->get(IntegrationLogs::getUrl())
            ->assertOk()
            ->assertSee('Logs Hub')
            ->assertSee('Incoming')
            ->assertSee('Outgoing')
            ->assertSee('tripay')
            ->assertSee('logs-live-001');
    }

    public function test_admin_is_redirected_from_integrations_cluster_root_to_connections(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin);

        $this->get(Integrations::getUrl())
            ->assertRedirect(ResellerIntegrationResource::getUrl());
    }
}
