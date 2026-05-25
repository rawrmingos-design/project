<?php

namespace Tests\Feature\Filament;

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

        $this->get(route('filament.admin.resources.inbound-source-policies.index'))->assertOk();
        $this->get(route('filament.admin.resources.inbound-source-policies.create'))->assertOk();
        $this->get(route('filament.admin.resources.inbound-source-policies.edit', ['record' => $policy]))->assertOk();
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

        $this->get(route('filament.admin.resources.inbound-source-policies.index'))->assertForbidden();
        $this->get(route('filament.admin.resources.inbound-source-policies.create'))->assertForbidden();
        $this->get(route('filament.admin.resources.inbound-source-policies.edit', ['record' => $policy]))->assertForbidden();
    }

    public function test_legacy_whitelisted_ips_table_is_removed_from_fresh_migrations(): void
    {
        $this->assertFalse(Schema::hasTable('whitelisted_ips'));
    }
}
