<?php

namespace Tests\Feature\Filament;

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

        $this->get(route('filament.admin.resources.reseller-integrations.index'))->assertOk();
        $this->get(route('filament.admin.resources.reseller-integrations.create'))->assertOk();
        $this->get(route('filament.admin.resources.reseller-integrations.edit', ['record' => $integration]))->assertOk();

        $this->get(route('filament.admin.resources.reseller-callback-profiles.index'))->assertOk();
        $this->get(route('filament.admin.resources.reseller-callback-profiles.create'))->assertOk();
        $this->get(route('filament.admin.resources.reseller-callback-profiles.edit', ['record' => $profile]))->assertOk();
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

        $this->get(route('filament.admin.resources.reseller-integrations.index'))->assertForbidden();
        $this->get(route('filament.admin.resources.reseller-integrations.create'))->assertForbidden();
        $this->get(route('filament.admin.resources.reseller-integrations.edit', ['record' => $integration]))->assertForbidden();

        $this->get(route('filament.admin.resources.reseller-callback-profiles.index'))->assertForbidden();
        $this->get(route('filament.admin.resources.reseller-callback-profiles.create'))->assertForbidden();
        $this->get(route('filament.admin.resources.reseller-callback-profiles.edit', ['record' => $profile]))->assertForbidden();
    }
}
