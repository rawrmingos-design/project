<?php

namespace Tests\Feature\Tenancy;

use App\Models\Deposit;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        parent::tearDown();
    }

    public function test_tenant_context_scopes_core_models(): void
    {
        [$tenantA, $tenantB] = $this->makeTenants();

        $orderA = Pembelian::factory()->create([
            'tenant_id' => $tenantA->id,
            'order_id' => 'TENANT-A-ORDER',
        ]);

        $orderB = Pembelian::factory()->create([
            'tenant_id' => $tenantB->id,
            'order_id' => 'TENANT-B-ORDER',
        ]);

        app(TenantContext::class)->set($tenantA);

        $this->assertTrue(Pembelian::query()->whereKey($orderA->id)->exists());
        $this->assertFalse(Pembelian::query()->whereKey($orderB->id)->exists());

        app(TenantContext::class)->set($tenantB);

        $this->assertFalse(Pembelian::query()->whereKey($orderA->id)->exists());
        $this->assertTrue(Pembelian::query()->whereKey($orderB->id)->exists());
    }

    public function test_tenant_context_auto_fills_tenant_id_on_create(): void
    {
        [$tenant] = $this->makeTenants();

        app(TenantContext::class)->set($tenant);

        $pembelian = Pembelian::factory()->create(['tenant_id' => null]);
        $pembayaran = Pembayaran::query()->create([
            'order_id' => 'PAY-' . uniqid(),
            'harga' => '10000',
            'no_pembayaran' => 'INV-' . uniqid(),
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'QRIS',
        ]);
        $deposit = Deposit::query()->create([
            'order_id' => 'DEP-' . uniqid(),
            'username' => 'tenant-user',
            'metode' => 'QRIS',
            'no_pembayaran' => 'DEP-REF',
            'jumlah' => 10000,
            'status' => 'Pending',
        ]);
        $user = User::factory()->create(['tenant_id' => null]);

        $this->assertSame($tenant->id, $pembelian->tenant_id);
        $this->assertSame($tenant->id, $pembayaran->tenant_id);
        $this->assertSame($tenant->id, $deposit->tenant_id);
        $this->assertSame($tenant->id, $user->tenant_id);
    }

    public function test_without_tenant_context_legacy_and_admin_queries_remain_unscoped(): void
    {
        [$tenant] = $this->makeTenants();

        $tenantOrder = Pembelian::factory()->create(['tenant_id' => $tenant->id]);
        $legacyOrder = Pembelian::factory()->create(['tenant_id' => null]);

        app(TenantContext::class)->clear();

        $this->assertTrue(Pembelian::query()->whereKey($tenantOrder->id)->exists());
        $this->assertTrue(Pembelian::query()->whereKey($legacyOrder->id)->exists());
    }

    private function makeTenants(): array
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        return [
            Tenant::query()->create([
                'owner_user_id' => $owner->id,
                'name' => 'Tenant A',
                'subdomain' => 'tenant-a',
                'tier' => 'starter',
                'status' => Tenant::STATUS_ACTIVE,
            ]),
            Tenant::query()->create([
                'owner_user_id' => $owner->id,
                'name' => 'Tenant B',
                'subdomain' => 'tenant-b',
                'tier' => 'business',
                'status' => Tenant::STATUS_ACTIVE,
            ]),
        ];
    }
}
