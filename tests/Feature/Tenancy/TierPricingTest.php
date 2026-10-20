<?php

namespace Tests\Feature\Tenancy;

use App\Models\Layanan;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TierPricing;
use App\Models\User;
use App\Tenancy\TenantPricingService;
use App\Tenancy\TenantProvisioningService;
use App\Tenancy\TierPricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mesin harga paket tenant.
 *
 * Angka yang diuji dihitung tangan dari rumus yang disepakati:
 *   modal_tenant = harga × (1 + platform_markup/100)
 *   harga_jual   = harga × (1 + tenant_markup/100)
 *   profit       = harga_jual − modal_tenant
 */
class TierPricingTest extends TestCase
{
    use RefreshDatabase;

    private function seedTiers(): void
    {
        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(TierPricingResolver::class)->flush();
    }

    private function makeTenant(string $tier, array $margin = ['markup_type' => 'percent', 'markup_value' => 8]): Tenant
    {
        return Tenant::query()->create([
            'name' => 'Toko ' . $tier,
            'subdomain' => 'toko-' . $tier,
            'tier' => $tier,
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => $margin,
        ]);
    }

    public function test_seeder_menyimpan_tiga_tier_dengan_markup_pilihan_b(): void
    {
        $this->seedTiers();

        $this->assertSame(3, TierPricing::query()->count());
        $this->assertSame(4.0, (float) TierPricing::query()->where('tier', 'starter')->value('platform_markup_percent'));
        $this->assertSame(3.0, (float) TierPricing::query()->where('tier', 'business')->value('platform_markup_percent'));
        $this->assertSame(2.0, (float) TierPricing::query()->where('tier', 'enterprise')->value('platform_markup_percent'));
    }

    public function test_seeder_idempoten_tidak_menimpa_angka_admin(): void
    {
        $this->seedTiers();

        TierPricing::query()->where('tier', 'starter')->update(['platform_markup_percent' => 7.5]);

        $this->seed(\Database\Seeders\TierPricingSeeder::class);

        $this->assertSame(7.5, (float) TierPricing::query()->where('tier', 'starter')->value('platform_markup_percent'));
        $this->assertSame(3, TierPricing::query()->count());
    }

    public function test_rumus_modal_tenant_dan_harga_jual_per_tier(): void
    {
        $this->seedTiers();

        // Harga pokok contoh nyata dari katalog produksi.
        $layanan = Layanan::factory()->create(['harga' => 171278]);

        // tangan: modal = 171278 × 1,04 = 178129.12 → 178129
        //        jual  = 171278 × 1,08 = 184980.24 → 184980
        $starter = app(TenantPricingService::class)->forLayanan($layanan, $this->makeTenant('starter'));
        $this->assertSame(171278, $starter['modal'], 'modal platform = layanans.harga');
        $this->assertSame(178129, $starter['modal_tenant']);
        $this->assertSame(184980, $starter['sell_price']);
        $this->assertSame(6851, $starter['profit']);

        // business: modal = ×1,03 = 176416.34 → 176416 ; jual tetap 184980 → profit 8564
        $business = app(TenantPricingService::class)->forLayanan($layanan, $this->makeTenant('business'));
        $this->assertSame(176416, $business['modal_tenant']);
        $this->assertSame(184980, $business['sell_price']);
        $this->assertSame(8564, $business['profit']);

        // enterprise: modal = ×1,02 = 174703.56 → 174704 ; jual tetap 184980 → profit 10276
        $enterprise = app(TenantPricingService::class)->forLayanan($layanan, $this->makeTenant('enterprise'));
        $this->assertSame(174704, $enterprise['modal_tenant']);
        $this->assertSame(184980, $enterprise['sell_price']);
        $this->assertSame(10276, $enterprise['profit']);
    }

    public function test_harga_jual_customer_identik_di_semua_tier(): void
    {
        $this->seedTiers();

        $layanan = Layanan::factory()->create(['harga' => 100000]);

        $harga = collect(['starter', 'business', 'enterprise'])
            ->map(fn (string $tier) => app(TenantPricingService::class)
                ->forLayanan($layanan, $this->makeTenant($tier))['sell_price'])
            ->all();

        // Janji "tier tinggi = untung lebih besar" hanya benar kalau harga
        // customer TIDAK ikut naik.
        // 100000 × 1,08 = 108000. Bukan 115000: markup platform TIDAK ditumpuk
        // ke harga customer, hanya ke titik modal tenant.
        $this->assertSame([108000, 108000, 108000], $harga);
    }

    public function test_profit_tenant_naik_tiap_naik_tier(): void
    {
        $this->seedTiers();

        $layanan = Layanan::factory()->create(['harga' => 100000]);

        $profit = collect(['starter', 'business', 'enterprise'])
            ->map(fn (string $tier) => app(TenantPricingService::class)
                ->forLayanan($layanan, $this->makeTenant($tier))['profit'])
            ->all();

        $this->assertTrue($profit[0] < $profit[1], 'business harus lebih untung dari starter');
        $this->assertTrue($profit[1] < $profit[2], 'enterprise harus lebih untung dari business');
    }

    public function test_markup_tenant_di_bawah_platform_dinaikkan_ke_batas_aman(): void
    {
        $this->seedTiers();

        // enterprise platform markup 2% — tenant set 1% (di bawah modalnya sendiri)
        $tenant = $this->makeTenant('enterprise', ['markup_type' => 'percent', 'markup_value' => 1]);
        $layanan = Layanan::factory()->create(['harga' => 100000]);

        $hasil = app(TenantPricingService::class)->forLayanan($layanan, $tenant);

        $this->assertGreaterThan(0, $hasil['profit'], 'tenant tidak boleh untung nol/negatif');
        $this->assertGreaterThan($hasil['modal_tenant'], $hasil['sell_price']);
    }

    public function test_markup_fixed_memakai_nominal_tetap(): void
    {
        $this->seedTiers();

        $tenant = $this->makeTenant('business', ['markup_type' => 'fixed', 'markup_value' => 5000]);
        $layanan = Layanan::factory()->create(['harga' => 100000]);

        $hasil = app(TenantPricingService::class)->forLayanan($layanan, $tenant, 2);

        // modal tenant = 200000 × 1,03 = 206000 ; jual = 206000 + 5000
        $this->assertSame(200000, $hasil['modal']);
        $this->assertSame(206000, $hasil['modal_tenant']);
        $this->assertSame(211000, $hasil['sell_price']);
        $this->assertSame(5000, $hasil['profit']);
    }

    public function test_harga_coret_dihitung_dari_persen_potongan(): void
    {
        $this->seedTiers();

        $starter = TierPricing::query()->where('tier', 'starter')->first();

        // 330000 / 0,8 = 412500 ; 800000 / 0,6 = 1333333
        $this->assertSame(412500, $starter->strikePriceForDuration(3));
        $this->assertSame(1333333, $starter->strikePriceForDuration(12));
        $this->assertSame(110000, $starter->pricePerMonthForDuration(3));
    }

    public function test_hanya_dua_durasi(): void
    {
        $this->assertSame([3, 12], TierPricing::DURATIONS);
    }

    public function test_resolver_fallback_kalau_tabel_kosong(): void
    {
        // tanpa seeder
        $resolver = app(TierPricingResolver::class);
        $tenant = new Tenant(['tier' => 'business']);

        $this->assertSame(3.0, $resolver->platformMarkupPercent($tenant));
        $this->assertSame(435000, $resolver->priceFor($tenant, 3));
        $this->assertSame(898000, $resolver->priceFor($tenant, 12));
    }

    public function test_invoice_durasi_12_bulan_memakai_harga_12_bulan_dan_periode_12_bulan(): void
    {
        $this->seedTiers();

        $tenant = Tenant::query()->create([
            'name' => 'Toko Dua Belas',
            'subdomain' => 'toko-dua-belas',
            'tier' => 'business',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);

        $owner = User::factory()->create(['role' => 'Member', 'tenant_id' => $tenant->id]);
        $tenant->forceFill(['owner_user_id' => $owner->id])->save();

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'business',
            'duration_months' => 12,
            'price' => 898000,
            'status' => Subscription::STATUS_PENDING,
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
        ]);

        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 898000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDay(),
            'metadata' => ['source' => 'test'],
        ]);

        app(TenantProvisioningService::class)->markInvoicePaid($invoice);

        $subscription->refresh();

        // 12 bulan, bukan 1 bulan seperti perilaku lama addMonth().
        $this->assertSame(
            12,
            (int) $subscription->current_period_start->diffInMonths($subscription->current_period_end)
        );
        $this->assertTrue($subscription->current_period_end->isAfter(now()->addMonths(11)));
    }

    public function test_pemilik_tenant_dapat_role_tenant_owner_bukan_gold(): void
    {
        $this->seedTiers();

        $tenant = Tenant::query()->create([
            'name' => 'Toko Owner',
            'subdomain' => 'toko-owner',
            'tier' => 'starter',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);

        $owner = User::factory()->create(['role' => 'Member', 'tenant_id' => $tenant->id]);
        $tenant->forceFill(['owner_user_id' => $owner->id])->save();

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'duration_months' => 3,
            'price' => 330000,
            'status' => Subscription::STATUS_PENDING,
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
        ]);

        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 330000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDay(),
            'metadata' => ['source' => 'test'],
        ]);

        app(TenantProvisioningService::class)->markInvoicePaid($invoice);

        $owner->refresh();

        $this->assertSame('tenant_owner', $owner->role);
        $this->assertTrue($owner->isTenantOwner());
        // tenant_owner BUKAN reseller: tidak dapat harga Gold, tidak diblok middleware.
        $this->assertFalse($owner->hasResellerAccess());
        $this->assertFalse($owner->isPremiumTier());
    }

    public function test_admin_tetap_admin_setelah_invoice_lunas(): void
    {
        $this->seedTiers();

        $tenant = Tenant::query()->create([
            'name' => 'Toko Admin',
            'subdomain' => 'toko-admin',
            'tier' => 'starter',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);

        $admin = User::factory()->create(['role' => 'Admin', 'tenant_id' => $tenant->id]);
        $tenant->forceFill(['owner_user_id' => $admin->id])->save();

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'duration_months' => 3,
            'price' => 330000,
            'status' => Subscription::STATUS_PENDING,
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
        ]);

        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 330000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDay(),
            'metadata' => ['source' => 'test'],
        ]);

        app(TenantProvisioningService::class)->markInvoicePaid($invoice);

        $this->assertSame('Admin', $admin->fresh()->role);
    }
}
