<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\ResellerCallbackProfiles\ResellerCallbackProfileResource;
use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use App\Models\ResellerCallbackProfile;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerOutboundResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_reseller_outbound_resource_pages(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $member = User::factory()->create(['role' => 'Member']);
        $integration = ResellerIntegration::query()->create([
            'user_id' => $member->getKey(),
            'integration_code' => 'live-resource-001',
            'mode' => 'live',
            'is_active' => true,
        ]);
        $profile = ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->getKey(),
            'is_enabled' => true,
            'callback_url' => 'https://client.example/callback',
            'webhook_secret' => 'resource-secret',
            'signing_algorithm' => 'sha256',
            'signature_header' => 'X-Callback-Signature',
            'version' => 1,
        ]);

        $this->actingAs($admin);

        $this->get(ResellerIntegrationResource::getUrl('index'))->assertOk();
        $this->get(ResellerIntegrationResource::getUrl('create'))->assertOk();
        $this->get(ResellerIntegrationResource::getUrl('edit', ['record' => $integration]))->assertOk();

        $this->get(ResellerCallbackProfileResource::getUrl('index'))->assertOk();
        $this->get(ResellerCallbackProfileResource::getUrl('create'))->assertOk();
        $this->get(ResellerCallbackProfileResource::getUrl('edit', ['record' => $profile]))->assertOk();
    }

    public function test_non_admin_cannot_access_reseller_outbound_resource_pages(): void
    {
        $member = User::factory()->create(['role' => 'Member']);
        $other = User::factory()->create(['role' => 'Member']);
        $integration = ResellerIntegration::query()->create([
            'user_id' => $other->getKey(),
            'integration_code' => 'live-resource-002',
            'mode' => 'live',
            'is_active' => true,
        ]);
        $profile = ResellerCallbackProfile::query()->create([
            'reseller_integration_id' => $integration->getKey(),
            'is_enabled' => true,
            'callback_url' => 'https://client.example/callback',
            'webhook_secret' => 'resource-secret',
            'signing_algorithm' => 'sha256',
            'signature_header' => 'X-Callback-Signature',
            'version' => 1,
        ]);

        $this->actingAs($member);

        $this->get(ResellerIntegrationResource::getUrl('index'))->assertForbidden();
        $this->get(ResellerIntegrationResource::getUrl('create'))->assertForbidden();
        $this->get(ResellerIntegrationResource::getUrl('edit', ['record' => $integration]))->assertForbidden();

        $this->get(ResellerCallbackProfileResource::getUrl('index'))->assertForbidden();
        $this->get(ResellerCallbackProfileResource::getUrl('create'))->assertForbidden();
        $this->get(ResellerCallbackProfileResource::getUrl('edit', ['record' => $profile]))->assertForbidden();
    }
}
