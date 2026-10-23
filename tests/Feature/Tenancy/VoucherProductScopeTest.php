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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Kupon punya DUA mode cakupan, dan dua-duanya harus benar-benar ditegakkan.
 *
 *   layanan_id NULL      -> berlaku di SEMUA produk (perilaku kupon lama)
 *   layanan_id terisi    -> hanya produk itu
 *
 * Sebelum ini, `Voucher::appliesToLayanan()` ada tapi TIDAK PERNAH dipanggil
 * dari mana pun. Field "Produk" di form admin/tenant hanya kosmetik: kupon yang
 * dibuat untuk satu produk tetap memotong produk lain lewat website, bot
 * Telegram/WhatsApp, maupun API. Jadi yang diuji di sini bukan sekadar "adanya
 * validasi", tapi bahwa kupon produk A benar-benar TIDAK memotong produk B di
 * setiap jalur.
 *
 * Aturan kedua yang diuji: kalau diskon membuat harga jatuh di bawah harga jual
 * platform (lantai), transaksi harus GAGAL — bukan dinaikkan ke lantai secara
 * diam-diam, karena pembeli melihat satu angka lalu ditagih angka lain.
 */
class VoucherProductScopeTest extends TestCase
{
    use RefreshDatabase;

    private const HARGA_POKOK = 100000;

    private Tenant $tenant;
    private Layanan $produkA;
    private Layanan $produkB;
    private User $pembeli;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false, 'app.url' => 'https://topupengine.test']);

        $this->mockSuccessfulAccountValidation();
        Http::fake(['ipinfo.io/*' => Http::response(['ip' => '127.0.0.1'], 200)]);

        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(\App\Tenancy\TierPricingResolver::class)->flush();

        DB::table('setting_webs')->insert([
            'id' => 1,
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
            'name' => 'Toko Cakupan',
            'subdomain' => 'toko-cakupan',
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 10.0],
        ]);

        $this->pembeli = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'pembeli-cakupan',
            'role' => 'Member',
            'balance' => 5000000,
            'no_wa' => '081234567890',
        ]);

        $kategori = Kategori::factory()->create(['tipe' => 'game', 'server_id' => 1]);

        // Dua produk dengan modal identik; yang membedakan hanya id.
        $this->produkA = $this->buatProduk($kategori->id, 'Produk A', 'sku-a');
        $this->produkB = $this->buatProduk($kategori->id, 'Produk B', 'sku-b');

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

    private function buatProduk(int $kategoriId, string $nama, string $sku): Layanan
    {
        return Layanan::factory()->create([
            'kategori_id' => $kategoriId,
            'layanan' => $nama,
            'provider' => 'manual',
            'provider_id' => $sku,
            'harga' => self::HARGA_POKOK,
            'harga_member' => self::HARGA_POKOK,
            'harga_platinum' => self::HARGA_POKOK,
            'harga_gold' => self::HARGA_POKOK,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
            'status' => 'available',
        ]);
    }

    private function kupon(?int $layananId, float $promo = 50.0, int $stock = 10): Voucher
    {
        return Voucher::create([
            'tenant_id' => $this->tenant->id,
            'layanan_id' => $layananId,
            'kode' => 'KUPON' . strtoupper(uniqid()),
            'promo' => $promo,
            'stock' => $stock,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);
    }

    private function hargaBot(Layanan $produk, string $kode)
    {
        return $this->actingAs($this->pembeli)->postJson(
            "https://{$this->tenant->subdomain}.topupengine.test/api/v2/order/price",
            [
                'nominal' => $produk->id,
                'voucher' => $kode,
                'ktg_tipe' => 'game',
            ]
        );
    }

    // ── Mode 1: kupon untuk SATU produk ────────────────────────────────────

    public function test_kupon_produk_tertentu_memotong_produk_itu(): void
    {
        // Promo 2% dipilih supaya masih di atas lantai: markup toko 10% hanya
        // menyisakan ruang diskon ~6,36%, karena lantai = modal tenant
        // (harga pokok × 1,03), bukan harga jual tenant.
        $kupon = $this->kupon($this->produkA->id, 2.0);

        $response = $this->hargaBot($this->produkA, $kupon->kode);

        $this->assertTrue((bool) $response->json('status'), $response->getContent());
        $this->assertGreaterThan(
            0,
            (int) $response->json('potongan'),
            'kupon harus memotong produk yang jadi sasarannya'
        );
    }

    public function test_kupon_produk_tertentu_tidak_memotong_produk_lain(): void
    {
        $kupon = $this->kupon($this->produkA->id, 2.0);

        $response = $this->hargaBot($this->produkB, $kupon->kode);

        $this->assertSame(
            0,
            (int) $response->json('potongan'),
            'kupon yang dibuat untuk Produk A tidak boleh memotong Produk B'
        );

        $this->assertFalse(
            (bool) $response->json('voucher_valid', true) === true
                && (int) $response->json('potongan') === 0
                && (bool) $response->json('status') === true
                && $response->json('message') === null,
            'respons harus jelas: kupon tidak berlaku untuk produk ini'
        );
    }

    // ── Mode 2: kupon untuk SEMUA produk ───────────────────────────────────

    public function test_kupon_semua_produk_memotong_produk_mana_pun(): void
    {
        $kupon = $this->kupon(null, 2.0);

        foreach ([$this->produkA, $this->produkB] as $produk) {
            $response = $this->hargaBot($produk, $kupon->kode);

            $this->assertTrue((bool) $response->json('status'), $response->getContent());
            $this->assertGreaterThan(
                0,
                (int) $response->json('potongan'),
                'kupon tanpa `layanan_id` harus berlaku di produk mana pun'
            );
        }
    }

    // ── Cakupan produk dijalankan juga di jalur website ────────────────────

    public function test_kupon_produk_tertentu_ditolak_di_checkout_website(): void
    {
        $kupon = $this->kupon($this->produkA->id, 2.0);

        $response = $this->actingAs($this->pembeli)->postJson(
            "https://{$this->tenant->subdomain}.topupengine.test/order/checkout",
            [
                'uid' => '778001',
                'zone' => '1234',
                'service' => $this->produkB->id,
                'payment_method' => 'SALDO',
                'nomor' => '081234567890',
                'ktg_tipe' => 'game',
                'voucher' => $kupon->kode,
            ]
        );

        $this->assertFalse(
            (bool) ($response->json('status') ?? false),
            'checkout website untuk produk lain harus menolak kupon ini: ' . $response->getContent()
        );

        $this->assertSame(
            0,
            Pembelian::query()->where('user_id', '778001')->count(),
            'tidak boleh ada pembelian tercatat saat kupon ditolak'
        );

        $this->assertSame(
            $kupon->stock,
            (int) $kupon->fresh()->stock,
            'stok kupon tidak boleh berkurang saat kupon ditolak'
        );
    }

    public function test_kupon_produk_tertentu_diterima_di_checkout_website_untuk_produknya(): void
    {
        $kupon = $this->kupon($this->produkA->id, 2.0);

        $response = $this->actingAs($this->pembeli)->postJson(
            "https://{$this->tenant->subdomain}.topupengine.test/order/checkout",
            [
                'uid' => '778002',
                'zone' => '1234',
                'service' => $this->produkA->id,
                'payment_method' => 'SALDO',
                'nomor' => '081234567890',
                'ktg_tipe' => 'game',
                'voucher' => $kupon->kode,
            ]
        );

        $this->assertTrue(
            (bool) ($response->json('status') ?? false),
            'checkout website untuk produk yang tepat harus berhasil: ' . $response->getContent()
        );

        $this->assertSame(
            1,
            Pembelian::query()->where('user_id', '778002')->count(),
            'pembelian harus tercatat'
        );
    }

    // ── Form admin platform: dua mode harus benar-benar tersimpan ─────────

    public function test_form_admin_platform_menyimpan_kupon_semua_produk(): void
    {
        // `CheckRole` membandingkan `role === 'Admin'` persis (kapital). Dengan
        // 'admin' lowercase, request cuma di-redirect ke dashboard — 302 yang
        // membuat `assertRedirect()` lolos padahal tidak ada yang tersimpan.
        $admin = User::factory()->create(['role' => 'Admin', 'tenant_id' => null]);

        // Route panel admin legacy ada di `/voucher` (lihat routes/web.php:497),
        // bukan `/admin/voucher`.
        $this->actingAs($admin)->post('https://topupengine.test/voucher', [
            'kode' => 'PLATFORMALL',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
            'layanan_id' => '',
        ])->assertRedirect();

        $this->assertDatabaseHas('vouchers', [
            'kode' => 'PLATFORMALL',
            'tenant_id' => null,
            'layanan_id' => null,
        ]);
    }

    public function test_form_admin_platform_menyimpan_kupon_produk_tertentu(): void
    {
        $admin = User::factory()->create(['role' => 'Admin', 'tenant_id' => null]);

        $this->actingAs($admin)->post('https://topupengine.test/voucher', [
            'kode' => 'PLATFORMA',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
            'layanan_id' => $this->produkA->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('vouchers', [
            'kode' => 'PLATFORMA',
            'tenant_id' => null,
            'layanan_id' => $this->produkA->id,
        ]);
    }

    // ── Aturan lantai: di bawah harga jual platform = GAGAL ────────────────

    public function test_diskon_di_bawah_lantai_platform_membuat_transaksi_gagal(): void
    {
        // Kupon 99% pada modal 100.000 -> harga jauh di bawah lantai platform.
        $kupon = $this->kupon(null, 99.0);

        $response = $this->hargaBot($this->produkA, $kupon->kode);

        $this->assertFalse(
            (bool) $response->json('status'),
            'harga setelah diskon di bawah lantai platform harus GAGAL, '
            . 'bukan dinaikkan diam-diam ke lantai. Respons: ' . $response->getContent()
        );
    }

    public function test_checkout_dengan_kupon_di_bawah_lantai_ditolak_dan_tidak_mencatat_order(): void
    {
        $kupon = $this->kupon($this->produkA->id, 99.0);

        $response = $this->actingAs($this->pembeli)->postJson(
            "https://{$this->tenant->subdomain}.topupengine.test/order/checkout",
            [
                'uid' => '777001',
                'zone' => '1234',
                'service' => $this->produkA->id,
                'payment_method' => 'SALDO',
                'nomor' => '081234567890',
                'ktg_tipe' => 'game',
                'voucher' => $kupon->kode,
            ]
        );

        $this->assertFalse(
            (bool) ($response->json('status') ?? false),
            'checkout di bawah lantai harus ditolak: ' . $response->getContent()
        );

        $this->assertSame(
            0,
            Pembelian::query()->where('user_id', '777001')->count(),
            'order yang ditolak tidak boleh tercatat sebagai pembelian'
        );

        $this->assertSame(
            $kupon->stock,
            (int) $kupon->fresh()->stock,
            'stok kupon harus dikembalikan penuh setelah penolakan'
        );
    }
}
