<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PublicOrderPageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SATU harga untuk produk yang sama, di jalur mana pun.
 *
 * Kenapa ini ada: setiap jalur harga memilih harga dasar sendiri-sendiri
 * (`match($role) { 'Gold' => harga_gold, ... }`) lalu menaruhnya ke
 * `$layanan->harga`. `TenantPricingService::applyToLayanan()` membaca field itu
 * sebagai ANCHOR MODAL, jadi harga jual platform diperlakukan sebagai modal dan
 * dimarkup lagi.
 *
 * Terukur di staging sebelum perbaikan (produk 2, tenant rawr markup 10%,
 * platform markup 3%):
 *
 *   DB               : pokok=2.995  harga_gold=3.085  harga_member=3.145
 *   etalase          : 3.295   (anchor pokok)      BENAR
 *   /order/price     : 3.394   (anchor harga_gold)  SALAH
 *
 * Untuk 342 dari 427 produk di staging, `harga_gold` LEBIH KECIL dari pokok
 * (rasio 0.972), sehingga toko justru menagih lebih murah dari seharusnya:
 *
 *   produk 311: pokok=9.762.627 harga_gold=9.486.326
 *   etalase   : 10.738.890  profit 683.384
 *   /order/price: 10.434.959 profit 379.453   <- margin hampir separuh hilang
 *
 * Tes ini membandingkan ANGKA YANG BENAR-BENAR KELUAR dari tiap jalur, bukan
 * membandingkan rumus yang disalin ke dalam tes.
 */
class TenantPriceParityAllRoutesTest extends TestCase
{
    use RefreshDatabase;

    private const HARGA_POKOK = 100000;
    private const HARGA_MEMBER = 106000;
    private const HARGA_GOLD = 108000;
    private const HARGA_PLATINUM = 104000;
    private const PLATFORM_MARKUP = 3.0;
    private const TENANT_MARKUP = 10.0;

    private Tenant $tenant;
    private Layanan $service;
    private User $pembeli;
    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false, 'app.url' => 'https://topupengine.test']);

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
            'name' => 'Toko Paritas',
            'subdomain' => 'toko-paritas',
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => self::TENANT_MARKUP],
        ]);

        $this->host = $this->tenant->subdomain . '.topupengine.test';

        $this->pembeli = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'pembeli-paritas-semua',
            'role' => 'Member',
            'balance' => 5000000,
            'no_wa' => '081234567890',
        ]);

        $kategori = Kategori::factory()->create([
            'tipe' => 'game',
            'kode' => 'uji-paritas',
            'status' => 'active',
        ]);

        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Produk Paritas',
            'provider' => 'manual',
            'provider_id' => 'manual-paritas',
            'harga' => self::HARGA_POKOK,
            'harga_member' => self::HARGA_MEMBER,
            'harga_gold' => self::HARGA_GOLD,
            'harga_platinum' => self::HARGA_PLATINUM,
            'profit_member' => 6,
            'profit_gold' => 8,
            'profit_platinum' => 4,
            'status' => 'available',
        ]);

        \App\Models\ProviderPath::query()->create([
            'layanan_id' => $this->service->id,
            'provider_code' => 'manual',
            'provider_sku' => 'manual-paritas',
            'modal_price' => self::HARGA_POKOK,
            'priority' => 1,
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

        // Etalase (`PublicOrderPageDataService`) tidak melewati middleware
        // `tenant.resolve`, jadi konteks tenant harus dipasang langsung.
        app(\App\Tenancy\TenantContext::class)->set($this->tenant);
    }

    protected function tearDown(): void
    {
        app(\App\Tenancy\TenantContext::class)->clear();

        parent::tearDown();
    }

    /** Harga jual tenant = pokok x (1 + markup tenant). */
    private function expectedTenantPrice(): int
    {
        return (int) round(self::HARGA_POKOK * (1 + (self::TENANT_MARKUP / 100)));
    }

    /** Modal tenant = pokok x (1 + markup platform). */
    private function expectedTenantModal(): int
    {
        return (int) round(self::HARGA_POKOK * (1 + (self::PLATFORM_MARKUP / 100)));
    }

    private function priceFromEtalase(): int
    {
        $data = app(PublicOrderPageDataService::class)->getData($this->service->kategori);

        foreach (($data['products'] ?? []) as $product) {
            if ((int) $product['id'] === (int) $this->service->id) {
                return (int) $product['price'];
            }
        }

        $this->fail('produk tidak ditemukan di payload etalase');
    }

    private function priceFromWebPreview(): int
    {
        $response = $this->postJson("https://{$this->host}/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        return (int) $response->json('harga');
    }

    private function priceFromApiPreview(): int
    {
        $response = $this->postJson("https://{$this->host}/api/v2/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        return (int) $response->json('harga');
    }

    public function test_etalase_memakai_harga_jual_tenant(): void
    {
        $this->assertSame(
            $this->expectedTenantPrice(),
            $this->priceFromEtalase(),
            'etalase harus menampilkan harga jual tenant, bukan harga platform.'
        );
    }

    public function test_pratinjau_web_sama_dengan_etalase(): void
    {
        $etalase = $this->priceFromEtalase();
        $preview = $this->priceFromWebPreview();

        $this->assertSame(
            $etalase,
            $preview,
            'Pratinjau /order/price harus menagih angka yang sama dengan yang '
            . 'ditampilkan etalase. Etalase: ' . $etalase . ', pratinjau: ' . $preview
        );
    }

    public function test_pratinjau_api_v2_sama_dengan_etalase(): void
    {
        $etalase = $this->priceFromEtalase();
        $api = $this->priceFromApiPreview();

        $this->assertSame(
            $etalase,
            $api,
            'Pratinjau API /api/v2/order/price harus sama dengan etalase. '
            . 'Etalase: ' . $etalase . ', API: ' . $api
        );
    }

    public function test_semua_jalur_pratinjau_memberi_angka_identik(): void
    {
        $etalase = $this->priceFromEtalase();
        $web = $this->priceFromWebPreview();
        $api = $this->priceFromApiPreview();

        $this->assertSame(
            [$etalase, $etalase],
            [$web, $api],
            'Tiga jalur pratinjau harga harus identik. '
            . "etalase={$etalase} web={$web} api={$api}"
        );
    }

    public function test_harga_array_tidak_pernah_di_bawah_modal_tenant(): void
    {
        foreach ([$this->priceFromEtalase(), $this->priceFromWebPreview(), $this->priceFromApiPreview()] as $price) {
            $this->assertGreaterThanOrEqual(
                $this->expectedTenantModal(),
                $price,
                'Harga yang ditampilkan tidak boleh di bawah modal tenant (Rp '
                . number_format($this->expectedTenantModal(), 0, ',', '.')
                . '): toko akan menjual rugi.'
            );
        }
    }

    public function test_nol_produk_ditampilkan_di_bawah_modal_tenant(): void
    {
        // Regresi untuk 342/427 produk staging yang `harga_gold`-nya di bawah
        // harga pokok. Kalau anchor-nya `harga_gold`, produk-produk itu tampil
        // lebih murah dari modal tenant.
        $murah = Layanan::factory()->create([
            'kategori_id' => $this->service->kategori_id,
            'layanan' => 'Produk Gold Di Bawah Pokok',
            'provider' => 'manual',
            'provider_id' => 'manual-murah',
            'harga' => self::HARGA_POKOK,
            'harga_member' => self::HARGA_POKOK,
            'harga_gold' => (int) round(self::HARGA_POKOK * 0.972),
            'harga_platinum' => self::HARGA_POKOK,
            'status' => 'available',
        ]);

        $data = app(PublicOrderPageDataService::class)->getData($this->service->kategori);

        foreach (($data['products'] ?? []) as $product) {
            $this->assertGreaterThanOrEqual(
                $this->expectedTenantModal(),
                (int) $product['price'],
                'Produk "' . ($product['name'] ?? '?') . '" tampil di bawah modal '
                . 'tenant. Penyebab paling umum: harga dasar diambil dari '
                . '`harga_gold`, yang untuk banyak produk justru lebih kecil dari '
                . 'harga pokok.'
            );
        }
    }

    public function test_platform_tanpa_tenant_tetap_memakai_harga_per_role(): void
    {
        // Perbaikan ini tidak boleh mengubah situs platform. Di sana tidak ada
        // TenantContext, jadi harga tetap harga per-peran.
        app(\App\Tenancy\TenantContext::class)->clear();

        // Route situs platform adalah `/id/harga`, bukan `/order/price` —
        // yang terakhir ada di dalam grup `tenant.resolve`.
        $response = $this->postJson('/id/harga', [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        // Guest di situs platform = harga_member (perilaku lama, tidak diubah).
        $this->assertSame(
            self::HARGA_MEMBER,
            (int) $response->json('harga'),
            'Situs platform tidak boleh berubah perilakunya.'
        );
    }
}
