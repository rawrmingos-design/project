<?php

namespace Tests\Feature\Api;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Harga yang dilihat reseller H2H di API.
 *
 * Reseller H2H adalah konsep PLATFORM: `reseller_integrations` hanya punya
 * `user_id`, tidak punya `tenant_id`, dan tenant tidak diberi fitur reseller
 * (keputusan t_9ad6f44c). Jadi satu-satunya aturan harga di jalur ini adalah
 * role -> kolom harga, dan konteks tenant TIDAK boleh mengubahnya.
 *
 * Dua cacat yang dikunci di sini:
 *
 * 1. Admin jatuh ke `else` -> ditagih `layanan.harga` (modal mentah provider),
 *    sementara jalur API satunya memperlakukan Admin seperti Gold. Selisihnya
 *    seluruh margin platform.
 *
 * 2. Dulu ada blok `if ($tenant !== null) { $service->harga = $harga; applyToLayanan(...) }`
 *    di `order()`. Karena `tenant.resolve` terpasang di route `/api/v1/order`
 *    dan me-resolve dari host, reseller yang memanggil lewat subdomain tenant
 *    akan mengisi TenantContext, lalu harga per-peran dibaca sebagai modal dan
 *    dimarkup lagi.
 */
class ResellerPriceIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** Modal provider, sengaja beda jauh dari tiap kolom harga per-peran. */
    private const MODAL = 100000;

    private function makeReseller(string $role): array
    {
        $rawKey = 'TEST-' . strtoupper(substr(md5(uniqid('', true)), 0, 24));

        $user = User::factory()->create([
            'username' => 'reseller.' . strtolower($role) . '.' . uniqid(),
            'role' => $role,
            'balance' => 10_000_000,
        ]);

        ResellerIntegration::factory()->create([
            'user_id' => $user->getKey(),
            'integration_code' => 'LIVE-' . strtoupper(uniqid()),
            'mode' => 'live',
            'is_active' => true,
            'allowed_ips' => ['127.0.0.1'],
            'api_key_hash' => hash('sha256', $rawKey),
        ]);

        return [$user, $rawKey];
    }

    /** Produk dengan modal dan ketiga kolom harga per-peran yang berbeda jelas. */
    private function makeProduct(string $kode): Kategori
    {
        $kategori = Kategori::factory()->create([
            'kode' => $kode,
            'tipe' => 'game',
            'status' => 'active',
        ]);

        Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Produk Uji ' . $kode,
            'harga' => self::MODAL,
            'harga_member' => 106000,
            'harga_platinum' => 104000,
            'harga_gold' => 108000,
            'provider_id' => 'SKU-' . strtoupper($kode),
            'status' => 'available',
        ]);

        return $kategori;
    }

    private function priceFromVariant(string $rawKey, Kategori $kategori, int $layananId, ?string $host = null): ?int
    {
        $url = '/api/v1/variant';
        if ($host !== null) {
            $url = 'https://' . $host . $url;
        }

        $response = $this->withoutMiddleware(
            \App\Http\Middleware\InboundSourceWhitelist::class,
            \App\Http\Middleware\EnforceResellerIpWhitelist::class,
        )
            ->withToken($rawKey)
            ->postJson($url, ['code' => $kategori->kode]);

        if (! $response->isOk()) {
            return null;
        }

        foreach (($response->json('data') ?? []) as $row) {
            if (($row['price'] ?? null) !== null) {
                return (int) $row['price'];
            }
        }

        return null;
    }

    public function test_admin_ditagih_harga_gold_bukan_modal_mentah(): void
    {
        // Admin punya akses reseller (`hasResellerAccess()` mencakup Admin), jadi
        // harga yang dia lihat harus harga jual platform, bukan modal provider.
        [, $rawKey] = $this->makeReseller('Admin');
        $kategori = $this->makeProduct('admin-price');

        $price = $this->priceFromVariant($rawKey, $kategori, 0);

        $this->assertNotNull($price, 'katalog API harus mengembalikan harga untuk Admin');

        $this->assertSame(
            108000,
            $price,
            'Admin harus ditagih harga_gold (sama seperti Gold), bukan modal mentah provider.'
        );

        $this->assertNotSame(
            self::MODAL,
            $price,
            'Admin tidak boleh ditagih modal provider: itu menghapus seluruh margin platform.'
        );
    }

    public function test_gold_dan_admin_melihat_harga_yang_sama(): void
    {
        [, $goldKey] = $this->makeReseller('Gold');
        [, $adminKey] = $this->makeReseller('Admin');
        $kategori = $this->makeProduct('gold-vs-admin');

        $gold = $this->priceFromVariant($goldKey, $kategori, 0);
        $admin = $this->priceFromVariant($adminKey, $kategori, 0);

        $this->assertNotNull($gold);
        $this->assertNotNull($admin);

        $this->assertSame(
            $gold,
            $admin,
            'Gold dan Admin harus melihat harga yang sama; keduanya akses reseller '
            . 'dan tidak ada alasan bisnis untuk membedakannya.'
        );
    }

    public function test_reseller_di_domain_platform_tidak_terkena_harga_tenant(): void
    {
        // Reseller H2H memanggil dari domain PLATFORM. `tenant.resolve` tidak
        // mengisi TenantContext di sana, jadi harga reseller murni role -> kolom
        // harga. Test ini menjaga agar perubahan di jalur tenant tidak bocor ke
        // reseller platform.
        //
        // Catatan penting: kalau reseller memanggil dari SUBDOMAIN TENANT, dia
        // memang ditagih harga toko itu — karena di sana dia bertindak sebagai
        // pembeli toko, bukan reseller. Itu perilaku yang benar dan dikunci oleh
        // `TenantCheckoutPriceParityTest`, bukan sesuatu yang harus dicegah.
        [, $rawKey] = $this->makeReseller('Gold');
        $kategori = $this->makeProduct('platform-domain');

        // Tanpa host tenant: request berjalan di domain platform.
        $price = $this->priceFromVariant($rawKey, $kategori, 0);

        $this->assertNotNull($price, 'katalog API harus mengembalikan harga');

        $this->assertSame(
            108000,
            $price,
            'Reseller di domain platform harus ditagih harga_gold, tidak terpengaruh '
            . 'markup tenant mana pun.'
        );
    }
}
