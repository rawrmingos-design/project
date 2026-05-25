<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\InboundSourcePolicies\InboundSourcePolicyResource;
use App\Models\InboundSourcePolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InboundSourcePolicyResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_inbound_whitelist_resource_pages(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $policy = InboundSourcePolicy::query()->create([
            'source_domain' => 'supplier_callback',
            'source_name' => 'digiflazz',
            'mode' => 'log_only',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        $this->get(InboundSourcePolicyResource::getUrl('index'))->assertOk();
        $this->get(InboundSourcePolicyResource::getUrl('create'))->assertOk();
        $this->get(InboundSourcePolicyResource::getUrl('edit', ['record' => $policy]))->assertOk();
    }

    public function test_non_admin_cannot_access_inbound_whitelist_resource_pages(): void
    {
        $member = User::factory()->create(['role' => 'Member']);
        $policy = InboundSourcePolicy::query()->create([
            'source_domain' => 'payment_gateway',
            'source_name' => 'tripay',
            'mode' => 'log_only',
            'is_active' => true,
        ]);

        $this->actingAs($member);

        $this->get(InboundSourcePolicyResource::getUrl('index'))->assertForbidden();
        $this->get(InboundSourcePolicyResource::getUrl('create'))->assertForbidden();
        $this->get(InboundSourcePolicyResource::getUrl('edit', ['record' => $policy]))->assertForbidden();
    }

    public function test_legacy_whitelisted_ips_table_is_removed_from_fresh_migrations(): void
    {
        $this->assertFalse(Schema::hasTable('whitelisted_ips'));
    }
}
