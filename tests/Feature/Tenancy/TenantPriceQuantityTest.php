<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P4 — quantity dikali dua kali di jalur web.
 *
 * `OrderController::price()` mengalikan `harga *= qty` untuk kategori ber-qty,
 * lalu memanggil `applyTenantPricing()` → `TenantPricingService::forLayanan()`
 * yang mengalikan `harga * quantity` SEKALI LAGI.
 *
 * Akibat: produk joki qty 2 ditagih 4× harga satuan, padahal seharusnya 2×.
 *
 * CATATAN PENTING: tes ini sengaja memakai RASIO, bukan angka mutlak.
 * Kolom harga pokok yang dipakai (`harga` vs `harga_gold`) adalah urusan P3/R0
 * dan masih bisa berubah. Yang diuji di sini murni aritmetika quantity:
 * qty 2 harus tepat 2× harga qty 1 — apa pun basis harganya.
 */
class TenantPriceQuantityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);
        config(['app.url' => 'https://topupengine.test']);
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        parent::tearDown();
    }

    private function makeTenant(string $subdomain, float $markup = 10.0): Tenant
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

    private function makeKategori(string $kode, string $tipe): Kategori
    {
        return Kategori::factory()->create([
            'kode' => $kode,
            'tipe' => $tipe,
            'status' => 'active',
        ]);
    }

    private function makeLayanan(Kategori $kategori, int $harga): Layanan
    {
        return Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'harga' => $harga,
            'status' => 'available',
        ]);
    }

    /** Harga jual yang dikembalikan endpoint price() untuk satu produk. */
    private function quoteHarga(string $subdomain, int $layananId, string $ktgTipe, int $qty): int
    {
        $response = $this->postJson(
            "http://{$subdomain}.topupengine.test/order/price",
            ['nominal' => $layananId, 'ktg_tipe' => $ktgTipe, 'qty' => $qty]
        );

        $response->assertOk();

        return (int) $response->json('harga');
    }

    public function test_qty_dua_tepat_dua_kali_harga_qty_satu(): void
    {
        $this->makeTenant('qty-joki');
        $kategori = $this->makeKategori('joki-ml', 'joki');
        $layanan = $this->makeLayanan($kategori, 50000);

        $qty1 = $this->quoteHarga('qty-joki', $layanan->id, 'joki', 1);
        $qty2 = $this->quoteHarga('qty-joki', $layanan->id, 'joki', 2);

        $this->assertSame(
            $qty1 * 2,
            $qty2,
            "qty 2 harus ditagih tepat 2x harga satuan (Rp {$qty1} -> Rp "
            . ($qty1 * 2) . '), bukan 4x (Rp ' . ($qty1 * 4) . "). Sekarang: Rp {$qty2}."
        );
    }

    public function test_qty_empat_tepat_empat_kali_harga_qty_satu(): void
    {
        $this->makeTenant('qty-empat');
        $kategori = $this->makeKategori('joki-valo', 'joki');
        $layanan = $this->makeLayanan($kategori, 25000);

        $qty1 = $this->quoteHarga('qty-empat', $layanan->id, 'joki', 1);
        $qty4 = $this->quoteHarga('qty-empat', $layanan->id, 'joki', 4);

        $this->assertSame($qty1 * 4, $qty4);
    }

    public function test_produk_non_joki_mengabaikan_qty(): void
    {
        $this->makeTenant('qty-nonjoki');
        $kategori = $this->makeKategori('diamond-ml', 'umum');
        $layanan = $this->makeLayanan($kategori, 30000);

        $qty1 = $this->quoteHarga('qty-nonjoki', $layanan->id, 'umum', 1);
        $qty5 = $this->quoteHarga('qty-nonjoki', $layanan->id, 'umum', 5);

        $this->assertSame(
            $qty1,
            $qty5,
            'Kategori tanpa qty: jumlah qty harus diabaikan sepenuhnya.'
        );
    }

    public function test_tanpa_tenant_qty_tetap_dikalikan(): void
    {
        // Jalur platform: `applyQuantityAwarePricing()` tidak menemukan tenant,
        // jadi controller mengalikan qty langsung. Jaring pengaman supaya
        // perbaikan P4 tidak diam-diam menghapus qty di situs platform.
        $kategori = $this->makeKategori('joki-platform', 'joki');
        $layanan = $this->makeLayanan($kategori, 50000);

        // Jalur platform memakai endpoint `/id/harga` (bukan `/order/price`
        // milik grup tenant). Keduanya memanggil `price()` yang sama.
        $qty1 = $this->postJson('http://topupengine.test/id/harga', [
            'nominal' => $layanan->id,
            'ktg_tipe' => 'joki',
            'qty' => 1,
        ])->assertOk();
        $qty2 = $this->postJson('http://topupengine.test/id/harga', [
            'nominal' => $layanan->id,
            'ktg_tipe' => 'joki',
            'qty' => 2,
        ])->assertOk();

        $this->assertSame(
            (int) $qty1->json('harga') * 2,
            (int) $qty2->json('harga'),
            'Di situs platform qty tetap harus dikalikan tepat sekali.'
        );
    }
}
