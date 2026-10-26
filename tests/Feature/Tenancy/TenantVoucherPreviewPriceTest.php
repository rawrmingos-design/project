<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Tenancy\TenantPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * PRATINJAU KUPON DI TOKO TENANT HARUS MEMAKAI HARGA JUAL TENANT.
 *
 * Kenapa tes ini ada
 * ------------------
 * `VoucherController::confirm()` (route `/check-voucher`) dan
 * `VoucherController::best()` (route `/available-voucher`) menghitung harga
 * dasar langsung dari kolom legacy `harga_member` / `harga_gold` /
 * `harga_platinum`. Keduanya TIDAK PERNAH memanggil `TenantPricingService`.
 *
 * Akibatnya, di toko tenant:
 *
 *   harga jual tenant   105.000   (100.000 x 1,05)
 *   yang ditampilkan    100.000   <- kolom harga_member
 *   mintrx dibandingkan 100.000   <- angka yang salah
 *   potongan dihitung   100.000   <- angka yang salah
 *
 * Yang paling berbahaya bukan angkanya, tapi KEPUTUSANNYA: kupon yang
 * sebenarnya sah bisa ditolak (mintrx tidak tercapai menurut angka platform),
 * dan kupon yang sebenarnya membuat transaksi jatuh di bawah modal tenant bisa
 * dinyatakan sah di layar pratinjau.
 *
 * Jalur tagih sudah benar sejak P3 (`CheckoutOrderService` memakai
 * `priceForRole()` + `floorViolation()`), jadi bug ini murni jalur TAMPIL.
 * Karena itu tesnya wajib memanggil ENDPOINT NYATA — kalau tesnya menyalin
 * rumus, ia akan hijau selamanya.
 *
 * Angka acuan (tenant enterprise, platform markup 2%, tenant markup 5%):
 *
 *   provider          100.000
 *   modal tenant      102.000   = 100.000 x 1,02
 *   harga jual tenant 105.000   = 100.000 x 1,05
 *   lantai harga      102.000   (harga jual platform = harga beli tenant)
 */
class TenantVoucherPreviewPriceTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Layanan $service;
    private User $customer;

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

        $owner = User::factory()->create(['role' => 'tenant_owner', 'balance' => 0]);

        $this->tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Warung Uji',
            'subdomain' => 'warung-uji',
            'tier' => 'enterprise',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 5],
        ]);

        $this->customer = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'pembeli-uji',
            'role' => 'Member',
            'balance' => 1000000,
            'no_wa' => '081234567890',
        ]);

        $kategori = Kategori::factory()->create(['tipe' => 'game', 'server_id' => 1]);

        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'provider' => 'manual',
            'provider_id' => 'manual-sku',
            'harga' => 100000,
            // Kolom legacy sengaja DIBAWA BEDA dari harga jual tenant, supaya
            // tes bisa membedakan "dibaca dari kolom legacy" vs "dihitung dari
            // service harga". Kalau keduanya sama, tes tidak mengukur apa pun.
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

    /** Harga acuan dihitung dari service, bukan ditebak di dalam tes. */
    private function tenantPrices(): array
    {
        return app(TenantPricingService::class)
            ->priceForRole($this->service->fresh(), 'Member', $this->tenant, 1);
    }

    private function tenantUrl(string $path): string
    {
        return "https://{$this->tenant->subdomain}.topupengine.test{$path}";
    }

    private function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'kode' => 'PROMO',
            'promo' => 10,
            'stock' => 100,
            'mintrx' => 0,
            'max_potongan' => 0,
            'expired_at' => null,
        ], $overrides));
    }

    /**
     * Inti bug: potongan dihitung dari harga JUAL tenant, bukan harga platform.
     *
     * Promo 10% dengan `max_potongan` 2.500 dipakai supaya hasil akhirnya TETAP
     * di atas modal tenant (dua perilaku diuji terpisah, jangan dicampur):
     *
     *   harga jual tenant 105.000 --potongan 2.500--> 102.500  (>= modal 102.000)
     *   harga platform    100.000 --potongan 2.500-->  97.500  (di bawah modal)
     *
     * Jadi kalau controller membaca `harga_member`, jawabannya 97.500 dan tes
     * ini gagal DUA kali: angkanya salah, dan (setelah lantai dipasang) malah
     * ditolak.
     */
    public function test_check_voucher_discount_is_computed_from_tenant_sell_price(): void
    {
        $this->makeVoucher(['promo' => 10, 'max_potongan' => 2500]);

        $prices = $this->tenantPrices();
        $this->assertSame(105000, $prices['unit'], 'prasyarat: harga jual tenant 105.000');

        $response = $this->actingAs($this->customer)
            ->postJson($this->tenantUrl('/check-voucher'), [
                'voucher' => 'PROMO',
                'service' => $this->service->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);

        $this->assertSame(
            102500,
            (int) $response->json('harga'),
            'potongan kupon di toko tenant harus dihitung dari harga jual tenant (105.000 - 2.500), bukan kolom legacy'
        );
    }

    /**
     * `mintrx` harus dibandingkan dengan harga yang benar-benar ditagih.
     *
     * mintrx 103.000:
     *   - dibanding harga jual tenant 105.000 -> LOLOS (benar)
     *   - dibanding harga platform    100.000 -> DITOLAK (salah)
     *
     * Jadi kupon yang sebenarnya sah ditolak di layar pratinjau. Pembeli
     * melihat "minimal transaksi tidak terpenuhi" padahal checkout menerimanya.
     */
    public function test_check_voucher_min_transaction_uses_tenant_sell_price(): void
    {
        $this->makeVoucher(['promo' => 0, 'mintrx' => 103000, 'max_potongan' => 0]);

        $response = $this->actingAs($this->customer)
            ->postJson($this->tenantUrl('/check-voucher'), [
                'voucher' => 'PROMO',
                'service' => $this->service->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => true]);
    }

    /**
     * Pratinjau tidak boleh menyatakan sah kupon yang hasil akhirnya jatuh di
     * bawah modal tenant. Sekarang cek lantai itu tidak ada sama sekali di
     * jalur pratinjau — hanya di jalur checkout.
     *
     * modal tenant 102.000. Promo 5% dari harga jual tenant 105.000 = 5.250,
     * hasil 99.750 -> DI BAWAH modal -> harus ditolak.
     */
    public function test_check_voucher_rejects_discount_that_falls_below_tenant_floor(): void
    {
        $this->makeVoucher(['promo' => 5, 'max_potongan' => 0]);

        $prices = $this->tenantPrices();
        $this->assertSame(102000, $prices['modal_tenant'], 'prasyarat: modal tenant 102.000');

        $response = $this->actingAs($this->customer)
            ->postJson($this->tenantUrl('/check-voucher'), [
                'voucher' => 'PROMO',
                'service' => $this->service->id,
            ]);

        $this->assertSame(
            false,
            $response->json('status'),
            'kupon yang membuat harga jatuh di bawah modal tenant tidak boleh dinyatakan sah di pratinjau'
        );
    }

    /**
     * `best()` punya bug yang sama, dan akibatnya lebih halus: `base_price`
     * yang dikirim ke UI adalah harga platform, jadi daftar promo di layar
     * menampilkan potongan yang tidak akan pernah didapat pembeli.
     */
    public function test_available_voucher_returns_tenant_base_price(): void
    {
        $this->makeVoucher(['promo' => 10, 'max_potongan' => 0]);

        $response = $this->actingAs($this->customer)
            ->postJson($this->tenantUrl('/available-voucher'), [
                'service' => $this->service->id,
            ]);

        $response->assertStatus(200);

        $this->assertSame(
            105000,
            (int) $response->json('base_price'),
            'base_price yang dikirim ke UI harus harga jual tenant, bukan harga platform'
        );

        $finalPrice = (int) ($response->json('vouchers.0.final_price') ?? 0);
        $this->assertSame(94500, $finalPrice, 'final_price kupon dihitung dari harga jual tenant');
    }

    /**
     * Situs platform tidak boleh ikut berubah: di sana harga memang harga
     * kolom role, tanpa markup tenant.
     */
    public function test_platform_site_check_voucher_still_uses_role_price(): void
    {
        $platformVoucher = Voucher::create([
            'tenant_id' => null,
            'kode' => 'PLATFORM10',
            'promo' => 10,
            'stock' => 100,
            'mintrx' => 0,
            'max_potongan' => 0,
            'expired_at' => null,
        ]);

        $platformUser = User::factory()->create([
            'role' => 'Gold',
            'balance' => 1000000,
            'no_wa' => '081234567891',
        ]);

        $response = $this->actingAs($platformUser)
            ->postJson('https://topupengine.test/check-voucher', [
                'voucher' => $platformVoucher->kode,
                'service' => $this->service->id,
            ]);

        $response->assertStatus(200);
        $this->assertSame(
            90000,
            (int) $response->json('harga'),
            'di situs platform harga tetap dari kolom role (100.000 - 10%)'
        );
    }
}
