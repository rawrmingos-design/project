<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use App\Tenancy\TenantPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantStorefrontPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        parent::tearDown();
    }

    public function test_tenant_pricing_uses_gold_cost_plus_percent_markup(): void
    {
        $tenant = $this->makeTenant([
            'margin_config' => [
                'markup_type' => 'percent',
                'markup_value' => 15,
            ],
        ]);
        $layanan = Layanan::factory()->create([
            'harga_gold' => 10000,
        ]);

        $price = app(TenantPricingService::class)->forLayanan($layanan, $tenant);

        $this->assertSame(10000, $price['modal']);
        $this->assertSame(1500, $price['profit']);
        $this->assertSame(11500, $price['sell_price']);
    }

    public function test_tenant_pricing_uses_fixed_markup(): void
    {
        $tenant = $this->makeTenant([
            'margin_config' => [
                'markup_type' => 'fixed',
                'markup_value' => 2500,
            ],
        ]);
        $layanan = Layanan::factory()->create([
            'harga_gold' => 10000,
        ]);

        $price = app(TenantPricingService::class)->forLayanan($layanan, $tenant, 2);

        $this->assertSame(20000, $price['modal']);
        $this->assertSame(2500, $price['profit']);
        $this->assertSame(22500, $price['sell_price']);
    }

    public function test_tenant_home_requires_resolved_tenant(): void
    {
        config(['app.url' => 'https://topupengine.test']);
        Kategori::factory()->create(['kode' => 'mobile-legends']);
        $this->makeTenant([
            'name' => 'Gacor Topup',
            'subdomain' => 'gacor',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->get('https://topupengine.test/order/mobile-legends')->assertNotFound();
        $this->get('https://gacor.topupengine.test/')->assertOk()->assertSee('Gacor Topup');
    }

    public function test_successful_tenant_order_credits_owner_commission_once(): void
    {
        $owner = User::factory()->create([
            'role' => 'Gold',
            'balance' => 0,
        ]);
        $tenant = $this->makeTenant([
            'owner_user_id' => $owner->id,
        ]);

        $order = Pembelian::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Proses',
            'profit' => 1750,
            'tenant_commission_credited_at' => null,
        ]);

        $order->update(['status' => 'Success']);
        $order->refresh()->update(['status' => 'Sukses']);

        $this->assertSame(1750, (int) $owner->fresh()->balance);
        $this->assertNotNull($order->fresh()->tenant_commission_credited_at);
    }

    private function makeTenant(array $attributes = []): Tenant
    {
        $owner = $attributes['owner_user_id'] ?? User::factory()->create([
            'role' => 'Gold',
            'balance' => 0,
        ])->id;

        return Tenant::query()->create(array_merge([
            'owner_user_id' => $owner,
            'name' => 'Tenant Store',
            'subdomain' => 'tenant-store-' . uniqid(),
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => [
                'markup_type' => 'percent',
                'markup_value' => 10,
            ],
        ], $attributes));
    }
}
