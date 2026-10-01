<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Gateway\GatewayCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Layar layanan Telegram: paket layanan sebagai kartu di dalam teks.
 *
 * Yang dijaga di sini adalah hal yang TIDAK terlihat dari kode formatter saja:
 * bahwa isi daftar benar-benar sampai ke user (dulu hanya hidup di tombol
 * inline yang tidak pernah terkirim), bahwa nomor pada keyboard angka menunjuk
 * item yang benar di layar yang sedang dilihat, dan bahwa jalur WhatsApp tidak
 * ikut berubah.
 */
class TelegramServicePackageScreenTest extends TestCase
{
    use RefreshDatabase;

    private const TG = BotGatewayCapabilities::SOURCE_TELEGRAM;

    private const WA = BotGatewayCapabilities::SOURCE_WHATSAPP;

    /**
     * Kategori + layanan + paket.
     *
     * @param  int  $serviceCount  Jumlah layanan di kategori ini.
     * @param  int|null  $inPackage  Jumlah layanan yang diikatkan ke paket;
     *   `null` = semua. Paket sengaja dibuat lewat pivot supaya relasi
     *   `Paket::layanan()` yang dipakai jalur produksi benar-benar teruji.
     */
    private function seedCatalog(
        int $serviceCount = 3,
        ?int $inPackage = null,
        string $packageName = '⭐ Spesial Items',
        string $categoryCode = 'mlbb',
    ): array {
        $type = CategoryType::query()->create([
            'name' => 'Top Up Games', 'slug' => 'top-up-games', 'sort' => 1,
        ]);
        $category = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => $categoryCode,
            'nama' => 'Mobile Legends',
            'status' => 'active',
        ]);

        $services = [];
        foreach (range(1, $serviceCount) as $index) {
            $services[] = Layanan::factory()->create([
                'kategori_id' => $category->id,
                'layanan' => "{$index} Diamond",
                'harga_member' => $index * 1000,
                'status' => 'available',
            ]);
        }

        $paket = null;
        if ($inPackage !== null && $inPackage > 0) {
            $paket = Paket::query()->create(['nama' => $packageName]);
            foreach (array_slice($services, 0, $inPackage) as $service) {
                $paket->layanan()->attach($service->id, ['product_logo' => null]);
            }
        }

        return [$category, $paket, $services];
    }

    private function fmt(): BotMessageFormatter
    {
        return app(BotMessageFormatter::class);
    }

    private function screen(string $code, ?int $package = null): array
    {
        $catalog = app(GatewayCatalogService::class);

        return $this->fmt()->formatServices(
            $catalog->services($code),
            1,
            BotGatewayCapabilities::forSource(self::TG),
            $catalog->servicePackages($code),
            $package,
        );
    }

    // ------------------------------------------------------------ isi kartu

    public function test_kartu_paket_berisi_nama_layanan_dan_harga_di_teks(): void
    {
        $this->seedCatalog(3, 3, '⭐ Spesial Items');

        $screen = $this->screen('mlbb');
        $text = (string) $screen['text'];

        $this->assertStringContainsString('╭───────────────', $text);
        $this->assertStringContainsString('╰───────────────', $text);
        $this->assertStringContainsString('┊ ⭐ Spesial Items', $text);
        $this->assertStringContainsString('┊・Layanan : 1 Diamond', $text);
        $this->assertStringContainsString('┊・Harga : Rp 1.000', $text);
    }

    public function test_daftar_tidak_lagi_disimpan_di_tombol_inline(): void
    {
        $this->seedCatalog(3, 3);

        $screen = $this->screen('mlbb');

        // Satu paket = satu halaman = tidak ada tombol pindah halaman. Yang
        // penting di sini: NOL tombol item. Kalau item kembali ke tombol inline,
        // layar akan kosong lagi begitu keyboard angka menang di adapter.
        $callbacks = [];
        foreach ((array) ($screen['buttons'] ?? []) as $row) {
            foreach ((array) $row as $button) {
                $callbacks[] = (string) ($button['callback'] ?? '');
            }
        }

        $this->assertSame([], $callbacks, 'Layar ini tidak boleh mengirim tombol item.');
    }

    public function test_daftar_paket_lebih_dari_satu_halaman_mengirim_tombol_pindah(): void
    {
        $this->seedCatalog(1, 1, 'Paket 0');

        // 9 paket = 2 halaman (8 per halaman). Paket tambahan dibuat langsung:
        // yang sedang diuji adalah paginasi layar, bukan cara paket dibuat.
        for ($i = 1; $i <= 8; $i++) {
            $extra = Paket::query()->create(['nama' => "Paket {$i}"]);
            $service = Layanan::factory()->create([
                'kategori_id' => Kategori::query()->where('kode', 'mlbb')->value('id'),
                'layanan' => "Item {$i}",
                'harga_member' => 1000,
                'status' => 'available',
            ]);
            $extra->layanan()->attach($service->id, ['product_logo' => null]);
        }

        $screen = $this->screen('mlbb');
        $entries = (array) ($screen['numeric_menu']['entries'] ?? []);

        // 8 entri konten di halaman 1, plus nomor 99 untuk maju.
        $this->assertArrayHasKey('8', $entries);
        $this->assertArrayNotHasKey('9', $entries);
        $this->assertArrayHasKey('99', $entries);
        $this->assertSame('layanan mlbb page:2', $entries['99']['command']);

        $this->assertSame(
            ['menu page:2'],
            array_map(
                static fn (array $row): string => (string) $row[0]['callback'],
                (array) ($screen['buttons'] ?? []),
            ),
            'Tanpa tombol ini halaman kedua hanya bisa dicapai dengan mengetik 99.',
        );
    }

    // ------------------------------------------------------------ nomor

    public function test_nomor_menunjuk_paket_dan_pilihan_membuka_isi_paket(): void
    {
        $this->seedCatalog(3, 3, '⭐ Spesial Items');

        $screen = $this->screen('mlbb');
        $entries = (array) ($screen['numeric_menu']['entries'] ?? []);

        $this->assertArrayHasKey('1', $entries);
        $this->assertSame('layanan mlbb paket:0', $entries['1']['command']);

        // Nomor yang sama, arti berbeda: di mode isi dia menunjuk layanan.
        $inside = $this->screen('mlbb', 0);
        $items = (array) ($inside['numeric_menu']['entries'] ?? []);

        $this->assertSame('metode', explode(' ', (string) $items['1']['command'])[0]);
    }

    public function test_nomor_0_kembali_dari_isi_paket_ke_daftar_paket(): void
    {
        $this->seedCatalog(3, 3);

        $inside = $this->screen('mlbb', 0);

        $this->assertArrayHasKey('0', (array) ($inside['numeric_menu']['entries'] ?? []));
        $this->assertSame(
            'layanan mlbb',
            (string) ($inside['numeric_menu']['entries']['0']['command'] ?? ''),
        );
    }

    public function test_layanan_tanpa_paket_tidak_ditampilkan(): void
    {
        // 2 terikat paket, 1 tidak — mengikuti storefront, yang mengabaikan
        // layanan tanpa paket begitu kategorinya punya paket.
        $this->seedCatalog(3, 2, '⭐ Spesial Items');

        $screen = $this->screen('mlbb');
        $text = (string) $screen['text'];

        $this->assertStringContainsString('1 Diamond', $text);
        $this->assertStringContainsString('2 Diamond', $text);
        $this->assertStringNotContainsString('3 Diamond', $text);
    }

    public function test_kategori_tanpa_paket_tetap_menampilkan_layanan(): void
    {
        // Tanpa satu pun paket, bot memakai daftar rata — kategori yang belum
        // ditata paketnya harus tetap bisa dibeli.
        $this->seedCatalog(3, null);

        $screen = $this->screen('mlbb');
        $text = (string) $screen['text'];

        $this->assertStringContainsString('┊・Layanan : 1 Diamond', $text);
        $this->assertStringContainsString('┊・Harga : Rp 1.000', $text);

        $entries = (array) ($screen['numeric_menu']['entries'] ?? []);
        $this->assertSame('metode', explode(' ', (string) $entries['1']['command'])[0]);
    }

    // ------------------------------------------------------------ navigasi

    public function test_hanya_paket_kosong_yang_dibuang(): void
    {
        [$category] = $this->seedCatalog(2, 2, '⭐ Spesial Items');
        $type = CategoryType::query()->where('slug', 'top-up-games')->firstOrFail();

        // Paket yang terisi, tapi isinya milik kategori LAIN. Paket begini tidak
        // menghasilkan satu pun kartu di kategori ini, jadi harus dibuang —
        // kalau tidak, user melihat judul paket tanpa isi yang bisa dipilih.
        $empty = Paket::query()->create(['nama' => '🕳️ Paket Kosong']);
        $other = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'other-game',
            'nama' => 'Other Game',
            'status' => 'active',
        ]);
        $foreign = Layanan::factory()->create([
            'kategori_id' => $other->id,
            'layanan' => 'Layanan Game Lain',
            'harga_member' => 5000,
            'status' => 'available',
        ]);
        $empty->layanan()->attach($foreign->id, ['product_logo' => null]);

        $screen = $this->screen('mlbb');
        $text = (string) $screen['text'];

        $this->assertStringNotContainsString('Paket Kosong', $text);
        $this->assertStringContainsString('⭐ Spesial Items', $text);
    }

    // ------------------------------------------------------------ WhatsApp

    public function test_whatsapp_tidak_ikut_berubah(): void
    {
        $this->seedCatalog(3, 3);
        $catalog = app(GatewayCatalogService::class);

        $wa = $this->fmt()->formatServices(
            $catalog->services('mlbb'),
            1,
            BotGatewayCapabilities::forSource(self::WA),
            $catalog->servicePackages('mlbb'),
            null,
        );

        // WA tetap memakai tombol, dan teksnya tetap judul saja.
        $this->assertSame('💎 *Mobile Legends*', $wa['text']);
        $texts = [];
        foreach ((array) ($wa['buttons'] ?? []) as $row) {
            foreach ((array) $row as $button) {
                $texts[] = $button['text'] ?? '';
            }
        }
        $this->assertContains('💎 1 Diamond · Rp 1.000', $texts);
        $this->assertNotContains('╭───────────────', [$wa['text']]);
    }

    public function test_whatsapp_tanpa_paket_tidak_berubah(): void
    {
        $this->seedCatalog(3, null);
        $catalog = app(GatewayCatalogService::class);

        $wa = $this->fmt()->formatServices(
            $catalog->services('mlbb'),
            1,
            BotGatewayCapabilities::forSource(self::WA),
            [],
            null,
        );

        $this->assertSame('💎 *Mobile Legends*', $wa['text']);
    }
}
