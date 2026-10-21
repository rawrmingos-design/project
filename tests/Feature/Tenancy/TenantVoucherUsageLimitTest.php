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
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Batas pemakaian kupon per pembeli.
 *
 * Kenapa perlu: `stock` membatasi total pemakaian, tapi tidak bisa menahan satu
 * orang memborong seluruh stok kupon. Untuk kupon berdiskon besar, itu berarti
 * satu akun bisa menghabiskan anggaran promo toko.
 */
class TenantVoucherUsageLimitTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Layanan $service;
    private User $pembeli;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false, 'app.url' => 'https://topupengine.test']);

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

        $owner = User::factory()->create(['role' => 'tenant_owner']);
        $this->tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Uji',
            'subdomain' => 'toko-uji',
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 5],
        ]);

        $this->pembeli = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'pembeli-uji',
            'role' => 'Member',
            'balance' => 5000000,
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

    private int $uidCounter = 0;

    private function checkout(?string $voucher = null)
    {
        // uid harus berbeda tiap percobaan: checkout yang sama persis
        // (uid+zone+service) ditolak guard idempotensi, bukan oleh kupon.
        $this->uidCounter++;

        $payload = [
            'uid' => '9000' . $this->uidCounter,
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890',
            'ktg_tipe' => 'game',
        ];

        if ($voucher !== null) {
            $payload['voucher'] = $voucher;
        }

        return $this->actingAs($this->pembeli)->postJson(
            "https://{$this->tenant->subdomain}.topupengine.test/order/checkout",
            $payload
        );
    }

    private function buatKupon(array $attributes = []): Voucher
    {
        return Voucher::create(array_merge([
            'tenant_id' => $this->tenant->id,
            'kode' => 'SEKALI',
            'promo' => 1.5,
            'stock' => 100,
            'mintrx' => 0,
            'max_potongan' => 0,
            'max_uses_per_user' => 1,
        ], $attributes));
    }

    public function test_pemakaian_kedua_ditolak_setelah_batas_tercapai(): void
    {
        $this->buatKupon();

        $this->checkout('SEKALI')->assertOk()->assertJson(['status' => true]);
        $this->assertSame(1, Pembelian::query()->count());

        // Lewati throttle 1 menit antar transaksi. Tanpa ini, penolakan yang
        // diuji bisa datang dari throttle — tes lolos karena alasan yang salah.
        $this->travel(2)->minutes();

        $response = $this->checkout('SEKALI')->assertOk()->assertJson(['status' => false]);

        // Pastikan yang menolak memang batas pemakaian, bukan guard lain.
        $this->assertStringContainsString('hanya bisa dipakai 1 kali', (string) $response->json('message'));
        $this->assertSame(1, Pembelian::query()->count(), 'order kedua tidak dibuat');
    }

    public function test_tanpa_batas_boleh_dipakai_berkali_kali(): void
    {
        $this->buatKupon(['max_uses_per_user' => null]);

        $this->checkout('SEKALI')->assertOk()->assertJson(['status' => true]);
        $this->travel(2)->minutes();
        $this->checkout('SEKALI')->assertOk()->assertJson(['status' => true]);

        $this->assertSame(2, Pembelian::query()->count());
    }

    public function test_order_gagal_tidak_menghabiskan_jatah(): void
    {
        $this->buatKupon();

        // Order pertama berstatus Gagal: tidak boleh dihitung sebagai pemakaian.
        Pembelian::query()->create([
            'order_id' => 'INV-GAGAL-1',
            'user_id' => $this->pembeli->id,
            'tenant_id' => $this->tenant->id,
            'layanan' => $this->service->layanan,
            'harga' => 100000,
            'status' => 'Gagal',
            'voucher' => 'SEKALI',
            'profit' => 0,
        ]);

        // Jatah masih utuh -> order berikutnya tetap bisa memakai kupon.
        $this->checkout('SEKALI')->assertOk()->assertJson(['status' => true]);
    }

    public function test_used_count_naik_saat_dipakai_dan_turun_saat_dikembalikan(): void
    {
        $voucher = $this->buatKupon();

        $this->checkout('SEKALI')->assertOk()->assertJson(['status' => true]);
        $this->assertSame(1, (int) $voucher->fresh()->used_count);

        // Order berakhir gagal -> VoucherService mengembalikan stok + used_count.
        $pembelian = Pembelian::query()->firstOrFail();
        $pembelian->forceFill(['status' => 'Gagal'])->save();

        app(VoucherService::class)->restoreStockForOrder($pembelian->fresh());

        // stok awal 100 -> dipotong 1 saat checkout -> dikembalikan jadi 100
        $this->assertSame(100, (int) $voucher->fresh()->stock, 'stok kembali');
        $this->assertSame(0, (int) $voucher->fresh()->used_count, 'used_count kembali');
    }
}
