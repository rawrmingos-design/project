<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\PublicOrderPageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * R2 — JARING PARITAS HARGA: satu harga untuk satu produk di SEMUA jalur.
 *
 * Kenapa tes ini terpisah dari `TenantPriceParityAllRoutesTest`
 * ------------------------------------------------------------
 * Tes paritas yang sudah ada membandingkan tiga jalur PRATINJAU (etalase, web,
 * api v2). Semua hijau, tapi dua lubang tetap lolos:
 *
 *   1. **Jalur gateway/bot tidak pernah diuji sama sekali.**
 *      `GatewayPricingService::resolveBaseAmount()` memilih kolom legacy
 *      `harga_member`/`harga_gold`/`harga_platinum` dan tidak memanggil
 *      `TenantPricingService`, jadi bot Telegram/WhatsApp menagih harga
 *      PLATFORM di toko tenant — sementara web menagih harga tenant.
 *
 *   2. **Tidak ada jalur yang memeriksa lantai modal di pratinjau.**
 *      `GatewayPricingService::resolveVoucherDiscount()` juga tidak memanggil
 *      `floorViolation()`, jadi kupon yang menjatuhkan harga di bawah modal
 *      tenant lolos di gateway dan baru meledak di checkout.
 *
 * Prinsip yang dipegang tes ini: SETIAP angka diambil dari endpoint nyata.
 * Tidak ada rumus yang disalin ke dalam tes, karena rumus salinan akan hijau
 * selamanya dan tidak mengukur apa pun.
 *
 * Angka acuan (tenant business, platform markup 3%, tenant markup 10%):
 *
 *   provider            100.000
 *   modal tenant        103.000   = 100.000 x 1,03
 *   harga jual tenant   110.000   = 100.000 x 1,10
 *   kolom legacy        106.000 / 108.000 / 104.000 (member/gold/platinum)
 *
 * Kalau ada jalur yang mengembalikan salah satu angka legacy itu, tes gagal.
 */
class TenantPriceJaringAllPathsTest extends TestCase
{
    use RefreshDatabase;

    private const HARGA_POKOK = 100000;
    private const HARGA_MEMBER = 106000;
    private const HARGA_GOLD = 108000;
    private const HARGA_PLATINUM = 104000;
    private const PLATFORM_MARKUP = 3.0;
    private const TENANT_MARKUP = 10.0;

    private Tenant $tenant;
    private Tenant $tenantLain;
    private Layanan $service;
    private User $pembeli;
    private string $host;

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

        $this->tenant = $this->makeTenant('toko-jaring', self::TENANT_MARKUP);
        // Tenant kedua dengan markup berbeda: memastikan tidak ada cache/state
        // yang membuat dua toko saling mencemari harga.
        $this->tenantLain = $this->makeTenant('toko-jaring-lain', 25.0);

        $this->host = $this->tenant->subdomain . '.topupengine.test';

        $this->pembeli = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'pembeli-jaring',
            'role' => 'Member',
            'balance' => 5000000,
            'no_wa' => '081234567890',
        ]);

        $kategori = Kategori::factory()->create([
            'tipe' => 'game',
            'kode' => 'uji-jaring',
            'status' => 'active',
        ]);

        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Produk Jaring',
            'provider' => 'manual',
            'provider_id' => 'manual-jaring',
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
            'provider_sku' => 'manual-jaring',
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

        app(\App\Tenancy\TenantContext::class)->set($this->tenant);
    }

    protected function tearDown(): void
    {
        app(\App\Tenancy\TenantContext::class)->clear();

        parent::tearDown();
    }

    private function makeTenant(string $subdomain, float $markup): Tenant
    {
        $owner = User::factory()->create(['role' => 'tenant_owner']);

        return Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko ' . $subdomain,
            'subdomain' => $subdomain,
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => $markup],
        ]);
    }

    /** Harga jual tenant dihitung dari service, bukan disalin rumusnya. */
    private function tenantPriceFor(?Tenant $tenant, int $qty = 1): int
    {
        return (int) app(\App\Tenancy\TenantPricingService::class)
            ->priceForRole($this->service->fresh(), 'Member', $tenant, $qty)['amount'];
    }

    private function tenantModalFor(?Tenant $tenant, int $qty = 1): int
    {
        return (int) app(\App\Tenancy\TenantPricingService::class)
            ->priceForRole($this->service->fresh(), 'Member', $tenant, $qty)['modal_tenant'];
    }

    // ---------------------------------------------------------------------
    // 1. JALUR GATEWAY / BOT — lubang terbesar, belum pernah diuji
    // ---------------------------------------------------------------------

    /**
     * Bot Telegram/WhatsApp memakai `GatewayPricingService::quote()` lewat
     * `POST /api/gateway/price`. Sebelum perbaikan jalur ini mengembalikan
     * harga kolom legacy (106.000), bukan harga jual tenant (110.000).
     */
    public function test_gateway_quote_charges_tenant_sell_price_not_legacy_column(): void
    {
        $expected = $this->tenantPriceFor($this->tenant);
        $this->assertSame(110000, $expected, 'prasyarat: harga jual tenant 110.000');

        $response = $this->postJson("https://{$this->host}/api/gateway/price", [
            'service_id' => $this->service->id,
            'payment_method' => 'SALDO',
            'ktg_tipe' => 'game',
        ]);

        $response->assertOk();

        $this->assertSame(
            $expected,
            (int) $response->json('data.base_amount'),
            'gateway/bot harus menagih harga jual tenant (110.000), bukan kolom legacy harga_member (106.000)'
        );
    }

    /**
     * Kupon di jalur gateway juga harus ditolak kalau hasilnya jatuh di bawah
     * modal tenant. Sekarang `resolveVoucherDiscount()` tidak memanggil
     * `floorViolation()` sama sekali.
     *
     * modal tenant 103.000; promo 10% dari 110.000 = 11.000 -> 99.000 < 103.000
     * -> harus DITOLAK, bukan diam-diam dijual rugi.
     */
    public function test_gateway_quote_rejects_voucher_below_tenant_floor(): void
    {
        Voucher::create([
            'tenant_id' => $this->tenant->id,
            'kode' => 'JARING10',
            'promo' => 10,
            'stock' => 100,
            'mintrx' => 0,
            'max_potongan' => 0,
            'expired_at' => null,
        ]);

        $modal = $this->tenantModalFor($this->tenant);
        $this->assertSame(103000, $modal, 'prasyarat: modal tenant 103.000');

        $response = $this->postJson("https://{$this->host}/api/gateway/price", [
            'service_id' => $this->service->id,
            'payment_method' => 'SALDO',
            'ktg_tipe' => 'game',
            'voucher' => 'JARING10',
        ]);

        // Ditolak = 422 dengan pesan validasi, bukan 200 dengan harga rugi.
        $this->assertSame(
            422,
            $response->status(),
            'kupon yang menjatuhkan harga di bawah modal tenant harus DITOLAK di gateway, bukan dijual rugi'
        );
    }

    // ---------------------------------------------------------------------
    // 2. PARITAS LINTAS JALUR — termasuk gateway yang tadinya tidak diuji
    // ---------------------------------------------------------------------

    public function test_all_four_paths_return_the_same_price(): void
    {
        $etalase = $this->priceFromEtalase();
        $web = $this->priceFromEndpoint('/order/price');
        $apiV2 = $this->priceFromEndpoint('/api/v2/order/price');
        $gateway = $this->priceFromGateway();

        $this->assertSame(
            [$etalase, $etalase, $etalase],
            [$web, $apiV2, $gateway],
            "Empat jalur harga harus identik. etalase={$etalase} web={$web} apiV2={$apiV2} gateway={$gateway}"
        );
    }

    // ---------------------------------------------------------------------
    // 3. QTY LINIER DI SEMUA JALUR (R2 poin 3)
    // ---------------------------------------------------------------------

    public function test_qty_is_linear_across_web_api_and_gateway(): void
    {
        $kategoriJoki = Kategori::factory()->create([
            'tipe' => 'joki',
            'kode' => 'uji-jaring-joki',
            'status' => 'active',
        ]);

        $joki = Layanan::factory()->create([
            'kategori_id' => $kategoriJoki->id,
            'layanan' => 'Jasa Joki',
            'provider' => 'manual',
            'provider_id' => 'manual-joki',
            'harga' => self::HARGA_POKOK,
            'harga_member' => self::HARGA_MEMBER,
            'harga_gold' => self::HARGA_GOLD,
            'harga_platinum' => self::HARGA_PLATINUM,
            'status' => 'available',
        ]);

        $unit = $this->tenantPriceFor($this->tenant, 1);
        $this->assertGreaterThan(0, $unit);

        foreach ([2, 3] as $qty) {
            $expected = $this->tenantPriceFor($this->tenant, $qty);
            $this->assertSame($unit * $qty, $expected, "prasyarat: qty {$qty} linier di service");

            $web = (int) $this->postJson("https://{$this->host}/order/price", [
                'nominal' => $joki->id, 'ktg_tipe' => 'joki', 'qty' => $qty,
            ])->assertOk()->json('harga');

            $api = (int) $this->postJson("https://{$this->host}/api/v2/order/price", [
                'nominal' => $joki->id, 'ktg_tipe' => 'joki', 'qty' => $qty,
            ])->assertOk()->json('harga');

            $gateway = (int) $this->postJson("https://{$this->host}/api/gateway/price", [
                'service_id' => $joki->id, 'payment_method' => 'SALDO', 'ktg_tipe' => 'joki', 'qty' => $qty,
            ])->assertOk()->json('data.base_amount');

            $this->assertSame($expected, $web, "web qty {$qty} harus {$expected}");
            $this->assertSame($expected, $api, "api v2 qty {$qty} harus {$expected}");
            $this->assertSame($expected, $gateway, "gateway qty {$qty} harus {$expected}");
        }
    }

    // ---------------------------------------------------------------------
    // 4. DUA TENANT BERBEDA TIDAK SALING MENCEMARI
    // ---------------------------------------------------------------------

    public function test_two_tenants_with_different_markup_do_not_leak_into_each_other(): void
    {
        $hargaA = $this->tenantPriceFor($this->tenant);
        $hargaB = $this->tenantPriceFor($this->tenantLain);

        $this->assertNotSame($hargaA, $hargaB, 'prasyarat: dua tenant punya markup berbeda');

        // Query etalase berurutan: kalau ada cache tanpa tenant id, tenant
        // kedua akan menerima harga tenant pertama.
        $etalaseA = (int) app(PublicOrderPageDataService::class)
            ->getData($this->service->kategori)['products'][0]['price']
            ?? 0;

        app(\App\Tenancy\TenantContext::class)->set($this->tenantLain);

        $etalaseB = (int) app(PublicOrderPageDataService::class)
            ->getData($this->service->kategori)['products'][0]['price']
            ?? 0;

        $this->assertNotSame(
            $etalaseA,
            $etalaseB,
            'harga etalase dua tenant tidak boleh sama — itu tanda cache tidak ter-scope per tenant'
        );
        $this->assertSame($hargaA, $etalaseA, 'etalase tenant A harus harga tenant A');
        $this->assertSame($hargaB, $etalaseB, 'etalase tenant B harus harga tenant B');
    }

    // ---------------------------------------------------------------------
    // 5. ZONA RUGI — tidak ada jalur yang menjual di bawah modal
    // ---------------------------------------------------------------------

    public function test_no_path_returns_below_tenant_modal(): void
    {
        $modal = $this->tenantModalFor($this->tenant);

        $paths = [
            'etalase' => $this->priceFromEtalase(),
            'web' => $this->priceFromEndpoint('/order/price'),
            'apiV2' => $this->priceFromEndpoint('/api/v2/order/price'),
            'gateway' => $this->priceFromGateway(),
        ];

        foreach ($paths as $name => $price) {
            $this->assertGreaterThanOrEqual(
                $modal,
                $price,
                "jalur {$name} menjual di bawah modal tenant (Rp " . number_format($modal, 0, ',', '.') . ")"
            );
        }
    }

    // ---------------------------------------------------------------------
    // helper — semua memanggil endpoint nyata
    // ---------------------------------------------------------------------

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

    private function priceFromEndpoint(string $path): int
    {
        return (int) $this->postJson("https://{$this->host}{$path}", [
            'nominal' => $this->service->id,
            'ktg_tipe' => 'game',
        ])->assertOk()->json('harga');
    }

    private function priceFromGateway(): int
    {
        return (int) $this->postJson("https://{$this->host}/api/gateway/price", [
            'service_id' => $this->service->id,
            'payment_method' => 'SALDO',
            'ktg_tipe' => 'game',
        ])->assertOk()->json('data.base_amount');
    }
}
