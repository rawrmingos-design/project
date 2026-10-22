<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Tenant;
use App\Models\TierPricing;
use App\Models\User;
use App\Models\Voucher;
use App\Tenancy\TierEntitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman kupon milik tenant.
 *
 * Dua hal yang dijaga:
 * - gate paket: `coupon` hanya untuk business+ (starter tidak dapat)
 * - isolasi: pemilik toko hanya bisa melihat/menghapus kuponnya sendiri
 */
class TenantVoucherPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeTenant(string $subdomain, string $tier): array
    {
        $owner = User::factory()->create(['role' => 'tenant_owner']);
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko ' . $subdomain,
            'subdomain' => $subdomain,
            'tier' => $tier,
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 8],
        ]);

        return [$owner, $tenant];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false, 'app.url' => 'https://topupengine.test']);

        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(\App\Tenancy\TierPricingResolver::class)->flush();
        app(TierEntitlements::class)->flush();
    }

    public function test_starter_tidak_bisa_membuka_halaman_kupon(): void
    {
        [$owner, $tenant] = $this->makeTenant('toko-starter', 'starter');

        $this->actingAs($owner)
            ->get("https://{$tenant->subdomain}.topupengine.test/vouchers")
            ->assertForbidden();
    }

    public function test_starter_tidak_bisa_membuat_kupon_lewat_request_langsung(): void
    {
        [$owner, $tenant] = $this->makeTenant('toko-starter', 'starter');

        $this->actingAs($owner)
            ->post("https://{$tenant->subdomain}.topupengine.test/vouchers", [
                'kode' => 'NEO',
                'promo' => 1,
                'stock' => 5,
            ])
            ->assertForbidden();

        $this->assertSame(0, Voucher::query()->count());
    }

    public function test_business_bisa_membuat_kupon_dan_tenant_id_terisi_otomatis(): void
    {
        [$owner, $tenant] = $this->makeTenant('toko-biz', 'business');

        $this->actingAs($owner)
            ->post("https://{$tenant->subdomain}.topupengine.test/vouchers", [
                'kode' => 'HEMAT2',
                'promo' => 2,
                'stock' => 20,
                'mintrx' => 50000,
                'max_potongan' => 5000,
            ])
            ->assertRedirect();

        $voucher = Voucher::query()->where('kode', 'HEMAT2')->firstOrFail();

        $this->assertSame($tenant->id, (int) $voucher->tenant_id, 'kupon otomatis milik tenant pembuatnya');
        $this->assertSame(2.0, (float) $voucher->promo);
        $this->assertSame(20, (int) $voucher->stock);
    }

    public function test_boleh_memakai_kode_yang_sama_dengan_voucher_platform(): void
    {
        [$owner, $tenant] = $this->makeTenant('toko-biz', 'business');

        Voucher::create([
            'tenant_id' => null,
            'kode' => 'PROMO10',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        // Perubahan perilaku yang disengaja: dengan isolasi ketat, kupon platform
        // tidak pernah terlihat di toko tenant, jadi kode yang sama tidak
        // bertabrakan. Sebelumnya ini ditolak karena kupon platform ikut
        // dianggap "terpakai" di toko tenant.
        $this->actingAs($owner)
            ->post("https://{$tenant->subdomain}.topupengine.test/vouchers", [
                'kode' => 'PROMO10',
                'promo' => 2,
                'stock' => 5,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            1,
            Voucher::query()->ownedBy($tenant->id)->where('kode', 'PROMO10')->count(),
            'kupon tenant dibuat'
        );

        $this->assertSame(
            1,
            Voucher::query()->platform()->where('kode', 'PROMO10')->count(),
            'kupon platform tidak tersentuh'
        );
    }

    public function test_kode_yang_sama_dengan_tenant_lain_tetap_boleh(): void
    {
        [$ownerA, $tenantA] = $this->makeTenant('toko-a', 'business');
        [, $tenantB] = $this->makeTenant('toko-b', 'business');

        Voucher::create([
            'tenant_id' => $tenantB->id,
            'kode' => 'PROMO10',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $this->actingAs($ownerA)
            ->post("https://{$tenantA->subdomain}.topupengine.test/vouchers", [
                'kode' => 'PROMO10',
                'promo' => 2,
                'stock' => 5,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Voucher::query()->where('kode', 'PROMO10')->count());
    }

    public function test_tidak_bisa_menghapus_kupon_tenant_lain(): void
    {
        [$ownerA, $tenantA] = $this->makeTenant('toko-a', 'business');
        [, $tenantB] = $this->makeTenant('toko-b', 'business');

        $milikB = Voucher::create([
            'tenant_id' => $tenantB->id,
            'kode' => 'MILIK-B',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $this->actingAs($ownerA)
            ->delete("https://{$tenantA->subdomain}.topupengine.test/vouchers/{$milikB->id}")
            ->assertNotFound();

        $this->assertNotNull($milikB->fresh(), 'kupon tenant lain tetap ada');
    }

    public function test_bukan_pemilik_tidak_bisa_membuka_halaman(): void
    {
        [, $tenant] = $this->makeTenant('toko-biz', 'business');
        $orangLain = User::factory()->create(['role' => 'Member']);

        $this->actingAs($orangLain)
            ->get("https://{$tenant->subdomain}.topupengine.test/vouchers")
            ->assertForbidden();
    }

    public function test_halaman_menampilkan_batas_aman_dari_margin_tenant(): void
    {
        [$owner, $tenant] = $this->makeTenant('toko-biz', 'business');

        $kategori = Kategori::factory()->create(['tipe' => 'game', 'server_id' => 1]);
        Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Produk Margin Tipis',
            'harga' => 100000,
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'status' => 'available',
        ]);

        $response = $this->actingAs($owner)
            ->get("https://{$tenant->subdomain}.topupengine.test/vouchers");

        $response->assertOk()
            ->assertSee('Batas aman promo')
            ->assertSee('ditolak saat customer membayar');
    }
}
