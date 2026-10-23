<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PublicOrderPageDataService;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * P1 — cache katalog bocor antar tenant.
 *
 * Cache key halaman order publik hanya memuat kode kategori + role:
 * `inertia_order_page:v5:{kode}:{role}`. Tidak ada tenant. TTL 300 detik, jadi
 * toko yang hit pertama melayani semua toko lain sampai cache kedaluwarsa.
 *
 * Yang diuji di sini BUKAN string cache key (itu hanya membuktikan penulisan
 * string, bukan perilaku), tapi isi payload: dua tenant dengan margin berbeda
 * harus menerima harga berbeda, termasuk saat cache sudah hangat.
 */
class TenantCatalogCacheIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://topupengine.test',
            'tenancy.disabled' => false,
        ]);

        Cache::flush();
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        Cache::flush();
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

    /** Jalankan service dengan konteks tenant tertentu, kembalikan harga produk pertama. */
    private function pricesFor(Tenant $tenant, Kategori $kategori): array
    {
        app(TenantContext::class)->set($tenant);

        $data = app(PublicOrderPageDataService::class)->getData($kategori);

        app(TenantContext::class)->clear();

        return collect($data['products'] ?? [])
            ->map(fn ($p) => (int) ($p['price'] ?? 0))
            ->values()
            ->all();
    }

    private function makeCatalog(): Kategori
    {
        $kategori = Kategori::factory()->create([
            'kode' => 'mlbb-cache',
            'tipe' => 'game',
            'status' => 'active',
        ]);

        Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'harga' => 10000,
            'harga_member' => 10000,
            'harga_gold' => 10000,
            'status' => 'available',
        ]);

        return $kategori;
    }

    public function test_harga_tidak_bocor_antar_tenant_tanpa_cache(): void
    {
        $murah = $this->makeTenant('cache-murah', 10.0);
        $mahal = $this->makeTenant('cache-mahal', 50.0);
        $kategori = $this->makeCatalog();

        $hargaMurah = $this->pricesFor($murah, $kategori);
        Cache::flush();
        $hargaMahal = $this->pricesFor($mahal, $kategori);

        $this->assertNotEmpty($hargaMurah, 'produk harus ada');
        $this->assertNotEmpty($hargaMahal, 'produk harus ada');

        $this->assertNotSame(
            $hargaMurah,
            $hargaMahal,
            'Dua tenant dengan markup berbeda tidak boleh menerima harga yang sama.'
        );
    }

    public function test_harga_tidak_bocor_saat_cache_sudah_hangat(): void
    {
        // Ini inti bug-nya: tenant pertama mengisi cache, tenant kedua membaca
        // isi cache milik tenant pertama karena key-nya tidak memuat tenant.
        $murah = $this->makeTenant('panas-murah', 10.0);
        $mahal = $this->makeTenant('panas-mahal', 50.0);
        $kategori = $this->makeCatalog();

        // Tenant murah lebih dulu — dia yang "memanaskan" cache.
        $hargaMurah = $this->pricesFor($murah, $kategori);

        // TANPA flush: cache masih hangat dari tenant murah.
        $hargaMahal = $this->pricesFor($mahal, $kategori);

        $this->assertNotEmpty($hargaMurah);
        $this->assertNotEmpty($hargaMahal);

        $this->assertNotSame(
            $hargaMurah,
            $hargaMahal,
            'Cache hangat milik tenant lain tidak boleh dipakai: harga tenant mahal '
            . 'ikut harga tenant murah. Ini kebocoran katalog antar tenant.'
        );
    }

    public function test_urutan_tenant_tidak_mengubah_harga(): void
    {
        // Sama seperti di atas tapi urutannya dibalik, supaya tidak lolos
        // hanya karena kebetulan tenant pertama yang diuntungkan.
        $murah = $this->makeTenant('balik-murah', 10.0);
        $mahal = $this->makeTenant('balik-mahal', 50.0);
        $kategori = $this->makeCatalog();

        $hargaMahal = $this->pricesFor($mahal, $kategori);
        $hargaMurah = $this->pricesFor($murah, $kategori);

        $this->assertNotSame(
            $hargaMahal,
            $hargaMurah,
            'Urutan kunjungan tenant tidak boleh menentukan harga yang dilihat tenant lain.'
        );
    }
}
