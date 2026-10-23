<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use App\Models\ResellerIntegration;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Harga di toko tenant harus SAMA lewat jalur mana pun.
 *
 * Kenapa ini ada: jalur web (`OrderController`) memakai `TenantPricingService`
 * lewat `applyTenantPricing()`, sedangkan bot Telegram/WhatsApp dan API reseller
 * langsung memakai `CheckoutOrderService::resolveServiceAmount()`, yang tidak
 * pernah memanggil `TenantPricingService` sama sekali. Akibatnya di toko tenant:
 *
 *   - pembeli lewat website  -> bayar harga jual tenant
 *   - pembeli lewat bot/API  -> bayar harga platform
 *
 * Dua harga untuk barang yang sama. Tenant kehilangan margin yang seharusnya
 * dia dapat, dan (lebih halus) lantai modal di `applyTenantMarginAfterDiscount`
 * tidak pernah berjalan di jalur bot, jadi voucher di sana tidak dijaga sama
 * sekali.
 *
 * Tes ini memakai satu produk, satu tenant, satu pembeli, lalu membandingkan
 * angka yang benar-benar tersimpan di `pembelians.harga`. Bukan sekadar
 * "tidak error": angkanya harus identik.
 */
class TenantCheckoutPriceParityTest extends TestCase
{
    use RefreshDatabase;

    /** Harga pokok platform (anchor modal), sengaja angka bulat. */
    private const HARGA_POKOK = 100000;

    /** Markup platform untuk tier business — dari `TierPricingSeeder`. */
    private const PLATFORM_MARKUP = 3.0;

    /** Markup toko yang dipasang di tenant. */
    private const TENANT_MARKUP = 10.0;

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
            'name' => 'Toko Paritas',
            'subdomain' => 'toko-paritas',
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => self::TENANT_MARKUP],
        ]);

        $this->pembeli = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'username' => 'pembeli-paritas',
            'role' => 'Member',
            'balance' => 5000000,
            'no_wa' => '081234567890',
        ]);

        $kategori = Kategori::factory()->create(['tipe' => 'game', 'server_id' => 1]);

        // profit_* sengaja 0 supaya kalau ada yang membaca kolom itu,
        // hasilnya 0 dan tidak diam-diam menutupi bug harga.
        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'provider' => 'manual',
            'provider_id' => 'manual-sku',
            'harga' => self::HARGA_POKOK,
            'harga_member' => self::HARGA_POKOK,
            'harga_platinum' => self::HARGA_POKOK,
            'harga_gold' => self::HARGA_POKOK,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
            'status' => 'available',
        ]);

        // Routing provider: `/api/v1/order` menyelesaikan `code` lewat
        // `layanans.provider_id` (di atas) atau `provider_paths`. Jalur kedua
        // ini yang dipakai supaya routing provider terisi wajar.
        \App\Models\ProviderPath::query()->create([
            'layanan_id' => $this->service->id,
            'provider_code' => 'manual',
            'provider_sku' => 'manual-sku',
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
    }

    /** Harga jual tenant yang diharapkan, dihitung dari rumus TenantPricingService. */
    private function expectedSellPrice(): int
    {
        $dasar = (int) round(self::HARGA_POKOK * (1 + (self::TENANT_MARKUP / 100)));

        return $dasar;
    }

    private function checkoutWeb(string $uid)
    {
        return $this->actingAs($this->pembeli)->postJson(
            "https://{$this->tenant->subdomain}.topupengine.test/order/checkout",
            [
                'uid' => $uid,
                'zone' => '1234',
                'service' => $this->service->id,
                'payment_method' => 'SALDO',
                'nomor' => '081234567890',
                'ktg_tipe' => 'game',
            ]
        );
    }

    private function checkoutApi(string $reference, string $rawKey)
    {
        return $this->withoutMiddleware([
                \App\Http\Middleware\InboundSourceWhitelist::class,
                \App\Http\Middleware\EnforceResellerIpWhitelist::class,
            ])
            ->withToken($rawKey)
            ->postJson("https://{$this->tenant->subdomain}.topupengine.test/api/v1/order", [
                // Kontrak API reseller: `code` = SKU provider, bukan id layanan.
                'code' => 'manual-sku',
                'referenceNumber' => $reference,
                'user_id' => '12345',
                'zone_id' => '1234',
                'payment_method' => 'SALDO',
            ]);
    }

    public function test_api_tenant_menagih_harga_yang_sama_dengan_web(): void
    {
        // 1) Jalur web — ini patokan yang benar.
        $this->checkoutWeb('90001')->assertOk()->assertJson(['status' => true]);

        $hargaWeb = (int) Pembelian::query()->where('user_id', '90001')->firstOrFail()->harga;

        // 2) Jalur API dari toko yang sama.
        $rawKey = 'TEST-PARITY-KEY';
        ResellerIntegration::factory()->create([
            'user_id' => $this->pembeli->getKey(),
            'integration_code' => 'PARITY-' . strtoupper(uniqid()),
            'mode' => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
            'api_key_hash' => hash('sha256', $rawKey),
        ]);

        $this->travel(2)->minutes();
        $response = $this->checkoutApi('REF-PARITY-' . uniqid(), $rawKey);

        // Respons reseller memakai amplop `{error, code, data}`; `data.price`
        // adalah angka yang ditagihkan ke pembeli.
        $this->assertFalse(
            (bool) $response->json('error'),
            'checkout API harus berhasil, respons: ' . $response->getContent()
        );

        $hargaApi = (int) $response->json('data.price');

        $this->assertSame(
            $this->expectedSellPrice(),
            $hargaWeb,
            'harga web harus memakai harga jual tenant'
        );

        $this->assertSame(
            $hargaWeb,
            $hargaApi,
            'harga API/bot harus SAMA dengan web — bukan harga platform. '
            . 'Web: ' . $hargaWeb . ', API: ' . $hargaApi
        );
    }

    /**
     * Kupon di jalur bot tidak boleh membuat harga jatuh di bawah modal tenant.
     *
     * `Api\OrderController::price()` menghitung potongan dari harga PLATFORM
     * (`harga_member`), bukan dari harga jual tenant. Jadi potongan dihitung
     * terhadap angka yang lebih kecil daripada yang seharusnya, dan hasil akhir
     * `asli − potongan` bisa berada di bawah modal tenant — toko menjual rugi.
     *
     * Jalur web tidak bisa melakukan ini: `applyTenantMarginAfterDiscount()`
     * menolaknya. Jadi ini bukan sekadar selisih harga, tapi lubang yang hanya
     * ada di jalur bot/API.
     */
    public function test_kupon_bot_tidak_boleh_menembus_modal_tenant(): void
    {
        // Kupon 50% milik tenant.
        \App\Models\Voucher::create([
            'tenant_id' => $this->tenant->id,
            'kode' => 'SETENGAH',
            'promo' => 50,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        $modalTenant = (int) round(self::HARGA_POKOK * (1 + (self::PLATFORM_MARKUP / 100)));

        $response = $this->actingAs($this->pembeli)->postJson(
            "https://{$this->tenant->subdomain}.topupengine.test/api/v2/order/price",
            [
                'nominal' => $this->service->id,
                'voucher' => 'SETENGAH',
                'ktg_tipe' => 'game',
            ]
        );

        $harga = (int) ($response->json('harga') ?? 0);

        $this->assertGreaterThanOrEqual(
            $modalTenant,
            $harga,
            sprintf(
                'harga bot setelah kupon (Rp %s) tidak boleh di bawah modal tenant (Rp %s)',
                number_format($harga, 0, ',', '.'),
                number_format($modalTenant, 0, ',', '.')
            )
        );
    }

    public function test_profit_tenant_tidak_nol_pada_checkout_api(): void
    {
        $rawKey = 'TEST-PARITY-PROFIT';
        ResellerIntegration::factory()->create([
            'user_id' => $this->pembeli->getKey(),
            'integration_code' => 'PARITYP-' . strtoupper(uniqid()),
            'mode' => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
            'api_key_hash' => hash('sha256', $rawKey),
        ]);

        $response = $this->checkoutApi('REF-PROFIT-' . uniqid(), $rawKey);
        $this->assertFalse((bool) $response->json('error'), $response->getContent());

        $order = Pembelian::query()->latest('id')->firstOrFail();

        // Margin tenant = harga jual − modal tenant (harga pokok + markup platform).
        $modalTenant = (int) round(self::HARGA_POKOK * (1 + (self::PLATFORM_MARKUP / 100)));
        $expectedProfit = $this->expectedSellPrice() - $modalTenant;

        $this->assertGreaterThan(0, $expectedProfit, 'prasyarat tes: margin tenant harus positif');

        $this->assertSame(
            $expectedProfit,
            (int) $order->profit,
            'profit harus margin tenant, bukan profit_member platform (yang di-set 0)'
        );
    }
}
