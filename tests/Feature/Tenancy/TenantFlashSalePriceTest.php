<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PublicOrderPageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Harga flash sale tidak boleh hilang di pratinjau.
 *
 * `OrderController::price()` dulu memasang harga flash lalu MENIMPANYA di baris
 * berikutnya lewat `applyQuantityAwarePricing()`:
 *
 *     if ($data->is_flash_sale == 1 && ...) {
 *         $data->harga = $data->harga_flash_sale;   // dipasang
 *     }
 *     $this->applyQuantityAwarePricing($data, $request);  // ditimpa markup penuh
 *
 * Akibatnya pembeli melihat harga flash di etalase, lalu pratinjau menampilkan
 * harga normal. Di toko tenant efeknya lebih buruk: harga flash dijepit di atas
 * modal tenant, karena diskon flash adalah uang tenant tapi toko tidak boleh
 * menjual di bawah harga belinya.
 */
class TenantFlashSalePriceTest extends TestCase
{
    use RefreshDatabase;

    private const HARGA_POKOK = 100000;
    private const TENANT_MARKUP = 10.0;
    private const PLATFORM_MARKUP = 3.0;
    // Sengaja DI ATAS modal tenant (103.000) dan DI BAWAH harga jual tenant
    // (110.000): flash sale yang wajar. Flash di bawah modal tenant dijepit,
    // dan itu dikunci tes tersendiri.
    private const FLASH_PRICE = 105000;

    private Tenant $tenant;
    private Layanan $service;
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
            'name' => 'Toko Flash',
            'subdomain' => 'toko-flash',
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => self::TENANT_MARKUP],
        ]);

        $this->host = $this->tenant->subdomain . '.topupengine.test';

        $kategori = Kategori::factory()->create([
            'tipe' => 'game',
            'kode' => 'uji-flash',
            'status' => 'active',
        ]);

        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Produk Flash',
            'provider' => 'manual',
            'provider_id' => 'manual-flash',
            'harga' => self::HARGA_POKOK,
            'harga_member' => self::HARGA_POKOK,
            'harga_gold' => self::HARGA_POKOK,
            'harga_platinum' => self::HARGA_POKOK,
            'is_flash_sale' => 1,
            'harga_flash_sale' => self::FLASH_PRICE,
            'stock_flash_sale' => 50,
            'expired_flash_sale' => now()->addDays(7)->toDateTimeString(),
            'status' => 'available',
        ]);

        app(\App\Tenancy\TenantContext::class)->set($this->tenant);
    }

    protected function tearDown(): void
    {
        app(\App\Tenancy\TenantContext::class)->clear();

        parent::tearDown();
    }

    private function modalTenant(): int
    {
        return (int) round(self::HARGA_POKOK * (1 + (self::PLATFORM_MARKUP / 100)));
    }

    public function test_pratinjau_web_memakai_harga_flash_yang_aktif(): void
    {
        $response = $this->postJson("https://{$this->host}/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        $this->assertSame(
            self::FLASH_PRICE,
            (int) $response->json('harga'),
            'Pratinjau harus menampilkan harga flash yang sedang aktif. '
            . 'Sebelumnya harga flash dipasang lalu ditimpa markup penuh, jadi '
            . 'pembeli melihat harga flash di etalase tapi harga normal di sini.'
        );
    }

    public function test_pratinjau_api_memakai_harga_flash_yang_sama(): void
    {
        $response = $this->postJson("https://{$this->host}/api/v2/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        $this->assertSame(
            self::FLASH_PRICE,
            (int) $response->json('harga'),
            'Pratinjau bot/API harus memberi harga flash yang sama dengan web.'
        );
    }

    public function test_etalase_dan_pratinjau_memberi_harga_flash_yang_sama(): void
    {
        $data = app(PublicOrderPageDataService::class)->getData($this->service->kategori);

        $flashEtalase = null;
        foreach (($data['products'] ?? []) as $product) {
            if ((int) $product['id'] === (int) $this->service->id) {
                $flashEtalase = (int) ($product['flashPrice'] ?? 0);
            }
        }

        $response = $this->postJson("https://{$this->host}/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $this->assertSame(
            $flashEtalase,
            (int) $response->json('harga'),
            'Angka flash di etalase dan di pratinjau harus sama. '
            . 'Etalase: ' . $flashEtalase . ', pratinjau: ' . $response->json('harga')
        );
    }

    public function test_harga_flash_dijepit_di_atas_modal_tenant(): void
    {
        // Harga flash di bawah modal tenant: toko tidak boleh menjual rugi,
        // walaupun flash sale adalah diskon milik tenant sendiri.
        $this->service->update(['harga_flash_sale' => 50000]);

        $response = $this->postJson("https://{$this->host}/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        $this->assertGreaterThanOrEqual(
            $this->modalTenant(),
            (int) $response->json('harga'),
            'Harga flash tidak boleh menjatuhkan harga di bawah modal tenant (Rp '
            . number_format($this->modalTenant(), 0, ',', '.') . ') — toko akan rugi.'
        );
    }

    public function test_flash_kedaluwarsa_tidak_dipakai(): void
    {
        $this->service->update(['expired_flash_sale' => now()->subDay()->toDateTimeString()]);

        $response = $this->postJson("https://{$this->host}/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        $this->assertSame(
            (int) round(self::HARGA_POKOK * (1 + (self::TENANT_MARKUP / 100))),
            (int) $response->json('harga'),
            'Flash sale kedaluwarsa harus diabaikan; harga kembali ke harga jual tenant.'
        );
    }

    public function test_flash_sale_harga_dikali_quantity(): void
    {
        $response = $this->postJson("https://{$this->host}/order/price", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'joki',
            'qty' => 3,
        ]);

        $response->assertOk();

        $this->assertSame(
            self::FLASH_PRICE * 3,
            (int) $response->json('harga'),
            'Harga flash adalah harga PER UNIT, jadi harus dikali quantity.'
        );
    }
}
