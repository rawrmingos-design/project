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
use App\Tenancy\TenantCommissionService;
use App\Tenancy\TenantPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SIAPA YANG MENANGGUNG DISKON VOUCHER DI SITUS TENANT.
 *
 * Model bisnisnya: tenant membeli dari platform dengan markup platform, lalu
 * menjual ke customer dengan markup sendiri. Diskon promo adalah keputusan
 * tenant, jadi biayanya harus jatuh ke uang tenant — bukan ke platform.
 *
 * Yang dijaga di sini adalah arah uangnya, dengan angka konkret:
 *
 *   provider            100.000   (harga pokok platform)
 *   markup platform 2%  -> 102.000   (harga jual platform ke tenant = modal tenant)
 *   markup tenant 5%    -> 105.000   (harga jual ke customer)
 *   margin tenant            3.000
 *
 * Kalau voucher membuat customer membayar di bawah 102.000, tenant menjual di
 * bawah modalnya → transaksi harus DITOLAK, karena tenant tidak punya cara
 * menutup selisih itu ke platform.
 *
 * Kalau voucher masih di atas 102.000, transaksi lolos TAPI margin tenant
 * mengecil sebesar diskon. Kalau margin tetap penuh, artinya platform yang
 * nombok — dan itu yang terjadi sekarang.
 */
class TenantVoucherLiabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Layanan $service;
    private Tenant $tenant;

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

        // enterprise: platform markup 2% — angka yang dipakai contoh di atas.
        $owner = User::factory()->create(['role' => 'tenant_owner', 'balance' => 0]);

        $this->tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Warung Uji',
            'subdomain' => 'warung-uji',
            'tier' => 'enterprise',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 5],
        ]);

        // Alur SALDO mencari ulang user lewat `username`
        // (`User::where('username', Auth::user()->username)`), dan `User` memakai
        // trait BelongsToTenant — jadi pembeli WAJIB milik tenant yang sama,
        // kalau tidak pencarian itu mengembalikan null dan checkout gagal
        // sebelum voucher sempat dihitung.
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

    /** Harga yang seharusnya, dihitung langsung dari service — bukan ditebak. */
    private function harga(): array
    {
        $layanan = $this->service->replicate();
        $layanan->id = $this->service->id;
        $layanan->exists = true;

        return app(TenantPricingService::class)->forLayanan($layanan, $this->tenant);
    }

    private function checkout(string $voucher): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->customer)->postJson(
            'https://warung-uji.topupengine.test/order/checkout',
            [
                'uid' => '12345',
                'zone' => '1234',
                'service' => $this->service->id,
                'payment_method' => 'SALDO',
                'nomor' => '081234567890',
                'voucher' => $voucher,
                'ktg_tipe' => 'game',
            ]
        );
    }

    public function test_angka_dasar_sesuai_model_bisnis(): void
    {
        $harga = $this->harga();

        // Angka yang dipakai contoh: 100.000 → (2%) 102.000 → (5%) 105.000
        $this->assertSame(100000, $harga['modal']);
        $this->assertSame(102000, $harga['modal_tenant']);
        $this->assertSame(105000, $harga['sell_price']);
        $this->assertSame(3000, $harga['profit']);
    }

    public function test_voucher_yang_bikin_harga_di_bawah_modal_tenant_ditolak(): void
    {
        // 5% dari 105.000 = 5.250 → customer bayar 99.750 < modal 102.000.
        $voucher = Voucher::create([
            'kode' => 'KEBABLASAN',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $response = $this->checkout('KEBABLASAN');

        $response->assertOk()->assertJson(['status' => false]);

        // Tidak ada order, stok voucher tidak terpakai, saldo tidak terpotong.
        $this->assertSame(0, Pembelian::query()->count());
        $this->assertSame(10, (int) $voucher->fresh()->stock);
        $this->assertSame(1000000, (int) $this->customer->fresh()->balance);
    }

    public function test_voucher_yang_masih_di_atas_modal_tenant_lolos(): void
    {
        // 1,5% dari 105.000 = 1.575 → customer bayar 103.425 > modal 102.000.
        $voucher = Voucher::create([
            'kode' => 'AMAN',
            'promo' => 1.5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $this->checkout('AMAN')->assertOk()->assertJson(['status' => true]);

        $order = Pembelian::query()->firstOrFail();

        $this->assertSame(103425, (int) $order->harga);
    }

    /**
     * Ini inti keluhannya: kalau diskon tidak mengurangi margin tenant, maka
     * platform yang membayar diskonnya. Angka di bawah membuktikannya.
     */
    public function test_margin_tenant_mengecil_sebesar_diskon_bukan_ditanggung_platform(): void
    {
        $harga = $this->harga();
        $modalTenant = $harga['modal_tenant'];
        $hargaJual = $harga['sell_price'];

        // Diskon yang masih aman tapi tidak nol: 1,5% → 1.575
        Voucher::create([
            'kode' => 'AMAN',
            'promo' => 1.5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $this->checkout('AMAN')->assertOk()->assertJson(['status' => true]);

        $order = Pembelian::query()->firstOrFail();
        $dibayarCustomer = (int) $order->harga;
        $diskon = $hargaJual - $dibayarCustomer;

        $this->assertSame(1575, $diskon, 'diskon 1,5% dari 105.000');

        // Margin tenant setelah diskon = yang dibayar customer − modal tenant.
        $marginSeharusnya = $dibayarCustomer - $modalTenant;
        $this->assertSame(1425, $marginSeharusnya);

        // Dan itulah yang dikreditkan ke saldo owner saat order sukses.
        // Kalau yang tersimpan masih 3.000 (margin penuh), berarti platform
        // yang menanggung 1.575 itu.
        $this->assertSame(
            $marginSeharusnya,
            (int) $order->profit,
            'profit order harus margin setelah diskon, bukan margin penuh'
        );

        $owner = $this->tenant->fresh()->owner;
        $owner->forceFill(['balance' => 0])->save();

        $order->update(['status' => 'Success']);
        $order->refresh()->update(['status' => 'Sukses']);

        $this->assertSame(
            $marginSeharusnya,
            (int) $owner->fresh()->balance,
            'komisi tenant = margin setelah diskon'
        );
    }

    public function test_tanpa_voucher_margin_tetap_penuh(): void
    {
        $this->actingAs($this->customer)->postJson(
            'https://warung-uji.topupengine.test/order/checkout',
            [
                'uid' => '12345',
                'zone' => '1234',
                'service' => $this->service->id,
                'payment_method' => 'SALDO',
                'nomor' => '081234567890',
                'ktg_tipe' => 'game',
            ]
        )->assertOk()->assertJson(['status' => true]);

        $order = Pembelian::query()->firstOrFail();

        $this->assertSame(105000, (int) $order->harga);
        $this->assertSame(3000, (int) $order->profit, 'tanpa diskon margin utuh');
    }
}
