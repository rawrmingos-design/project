<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Kebocoran isolasi voucher, diuji lewat CHECKOUT NYATA.
 *
 * Sebelum `vouchers.tenant_id` ada, semua lookup memakai
 * `Voucher::where('kode', ...)` tanpa scope: kode buatan tenant A berlaku di
 * toko tenant B. Tes ini membuktikannya di jalur uang — bukan hanya di level
 * query — karena inilah yang sebenarnya dilakukan customer.
 */
class TenantVoucherIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private Layanan $service;
    private User $pembeliA;
    private User $pembeliB;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenancy.disabled' => false,
            'app.url' => 'https://topupengine.test',
        ]);

        $this->mockSuccessfulAccountValidation();
        Http::fake(['ipinfo.io/*' => Http::response(['ip' => '127.0.0.1'], 200)]);

        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(\App\Tenancy\TierPricingResolver::class)->flush();

        SettingWeb::query()->create([
            'judul_web' => 'Platform Uji',
            'deskripsi_web' => 'Deskripsi',
            'keywords' => 'uji',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/uji',
            'url_tiktok' => 'https://tiktok.com/@uji',
            'url_youtube' => 'https://youtube.com/uji',
            'url_fb' => 'https://facebook.com/uji',
            'topupindo_api' => 'topupindo',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'order_prefik' => 'INV',
            'paydisini_apikey' => 'key',
        ]);

        $this->tenantA = $this->makeTenant('toko-a', 'business');
        $this->tenantB = $this->makeTenant('toko-b', 'business');

        $this->pembeliA = $this->makePembeli($this->tenantA, 'pembeli-a');
        $this->pembeliB = $this->makePembeli($this->tenantB, 'pembeli-b');

        $kategori = Kategori::factory()->create(['tipe' => 'game', 'server_id' => 1]);

        // enterprise margin tipis supaya voucher kecil pun perlu dihitung.
        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'provider' => 'manual',
            'provider_id' => 'manual-sku',
            'harga' => 100000,
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'profit_member' => 5,
            'profit_platinum' => 5,
            'profit_gold' => 5,
            'catatan' => 'Uji',
            'status' => 'available',
        ]);

        Method::create([
            'name' => 'Saldo',
            'code' => 'SALDO',
            'tipe' => 'saldo',
            'payment' => 'saldo',
            'images' => 'saldo.png',
            'keterangan' => 'Bayar pakai saldo',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);
    }

    private function makeTenant(string $subdomain, string $tier): Tenant
    {
        $owner = User::factory()->create(['role' => 'tenant_owner']);

        return Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko ' . $subdomain,
            'subdomain' => $subdomain,
            'tier' => $tier,
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 5],
        ]);
    }

    private function makePembeli(Tenant $tenant, string $username): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'username' => $username,
            'role' => 'Member',
            'balance' => 1000000,
            'no_wa' => '081234567890',
        ]);
    }

    private function checkout(Tenant $tenant, User $pembeli, ?string $voucher = null)
    {
        $payload = [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890',
            'ktg_tipe' => 'game',
        ];

        if ($voucher !== null) {
            $payload['voucher'] = $voucher;
        }

        return $this->actingAs($pembeli)->postJson(
            "https://{$tenant->subdomain}.topupengine.test/order/checkout",
            $payload
        );
    }

    public function test_voucher_tenant_a_tidak_berlaku_di_toko_tenant_b(): void
    {
        Voucher::create([
            'tenant_id' => $this->tenantA->id,
            'kode' => 'RAHASIA-A',
            'promo' => 1.5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        // Di toko A: berlaku, harga turun.
        $this->checkout($this->tenantA, $this->pembeliA, 'RAHASIA-A')
            ->assertOk()
            ->assertJson(['status' => true]);

        $hargaA = (int) Pembelian::query()->latest('id')->first()->harga;
        $this->assertLessThan(105000, $hargaA, 'voucher tenant A berlaku di toko A');

        // Di toko B: HARUS ditolak — kode itu bukan miliknya.
        $sebelum = Pembelian::query()->count();

        $this->checkout($this->tenantB, $this->pembeliB, 'RAHASIA-A')
            ->assertOk()
            ->assertJson(['status' => false]);

        $this->assertSame($sebelum, Pembelian::query()->count(), 'tidak ada order dibuat di toko B');
    }

    public function test_voucher_platform_berlaku_di_kedua_toko(): void
    {
        Voucher::create([
            'tenant_id' => null,
            'kode' => 'PROMO-PLATFORM',
            'promo' => 1.5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $this->checkout($this->tenantA, $this->pembeliA, 'PROMO-PLATFORM')
            ->assertOk()->assertJson(['status' => true]);

        $this->checkout($this->tenantB, $this->pembeliB, 'PROMO-PLATFORM')
            ->assertOk()->assertJson(['status' => true]);

        $this->assertSame(2, Pembelian::query()->count());
    }

    public function test_stok_dipotong_pada_voucher_tenant_yang_benar(): void
    {
        $voucherA = Voucher::create([
            'tenant_id' => $this->tenantA->id,
            'kode' => 'SAMA',
            'promo' => 1.5,
            'stock' => 5,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $voucherB = Voucher::create([
            'tenant_id' => $this->tenantB->id,
            'kode' => 'SAMA',
            'promo' => 1.5,
            'stock' => 5,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $this->checkout($this->tenantB, $this->pembeliB, 'SAMA')
            ->assertOk()->assertJson(['status' => true]);

        $this->assertSame(5, (int) $voucherA->fresh()->stock, 'stok voucher tenant A tidak tersentuh');
        $this->assertSame(4, (int) $voucherB->fresh()->stock, 'stok voucher tenant B terpotong');
    }

    public function test_rollback_mengembalikan_stok_ke_tenant_yang_benar(): void
    {
        $voucherA = Voucher::create([
            'tenant_id' => $this->tenantA->id,
            'kode' => 'SAMA',
            'promo' => 50,
            'stock' => 5,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $voucherB = Voucher::create([
            'tenant_id' => $this->tenantB->id,
            'kode' => 'SAMA',
            'promo' => 50,
            'stock' => 5,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        // 50% bikin harga jatuh di bawah modal → ditolak, stok harus kembali.
        $this->checkout($this->tenantB, $this->pembeliB, 'SAMA')
            ->assertOk()->assertJson(['status' => false]);

        $this->assertSame(5, (int) $voucherA->fresh()->stock, 'stok tenant A utuh');
        $this->assertSame(5, (int) $voucherB->fresh()->stock, 'stok tenant B dikembalikan');
    }
}
