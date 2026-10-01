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
 * Layar layanan Telegram: SATU daftar rata, satu baris per layanan.
 *
 * Yang dijaga di sini adalah hal yang tidak terlihat dari membaca formatter:
 *
 * - isi daftar benar-benar sampai ke user (dulu hanya hidup di tombol inline
 *   yang tidak pernah terkirim, sehingga layarnya tampil kosong);
 * - nama paket TIDAK muncul lagi (keputusan user: cukup nama layanannya);
 * - paket tetap menentukan ISI daftar — layanan tanpa paket disembunyikan;
 * - kategori yang tidak punya layanan berpaket TIDAK dipajang, supaya tidak
 *   ada kategori yang dibuka lalu kosong;
 * - jalur WhatsApp tidak ikut berubah.
 */
class TelegramServiceListScreenTest extends TestCase
{
    use RefreshDatabase;

    private const TG = BotGatewayCapabilities::SOURCE_TELEGRAM;

    private const WA = BotGatewayCapabilities::SOURCE_WHATSAPP;

    /**
     * Kategori + layanan + paket.
     *
     * @param  int  $serviceCount  Jumlah layanan di kategori ini.
     * @param  int|null  $inPackage  Jumlah layanan yang diikatkan ke paket;
     *   `null` = tidak ada paket sama sekali.
     */
    private function seedCatalog(
        int $serviceCount = 3,
        ?int $inPackage = null,
        string $packageName = '⚡ Proses Instant',
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

    private function screen(string $code, int $page = 1): array
    {
        $catalog = app(GatewayCatalogService::class);

        return app(BotMessageFormatter::class)->formatServices(
            $catalog->services($code),
            $page,
            BotGatewayCapabilities::forSource(self::TG),
            $catalog->packagedServices($code),
        );
    }

    // --------------------------------------------------------------- isi daftar

    public function test_daftar_berisi_nama_layanan_dan_harga_satu_baris_per_item(): void
    {
        $this->seedCatalog(3, 3);

        $text = (string) $this->screen('mlbb')['text'];

        $this->assertStringContainsString('[1]. 1 Diamond — Rp 1.000', $text);
        $this->assertStringContainsString('[2]. 2 Diamond — Rp 2.000', $text);
        $this->assertStringContainsString('[3]. 3 Diamond — Rp 3.000', $text);
    }

    public function test_nama_paket_tidak_ditampilkan(): void
    {
        $this->seedCatalog(3, 3, '⚡ Proses Instant');

        $screen = $this->screen('mlbb');
        $text = (string) $screen['text'];

        $this->assertStringNotContainsString('Proses Instant', $text);
        $this->assertStringNotContainsString('┊・Layanan :', $text);
        $this->assertStringNotContainsString('╭─', $text);
        $this->assertStringNotContainsString('📦', $text);
    }

    public function test_daftar_tidak_disimpan_di_tombol_inline(): void
    {
        $this->seedCatalog(3, 3);

        $callbacks = [];
        foreach ((array) ($this->screen('mlbb')['buttons'] ?? []) as $row) {
            foreach ((array) $row as $button) {
                $callbacks[] = (string) ($button['callback'] ?? '');
            }
        }

        // Satu halaman = tidak ada tombol pindah halaman, dan NOL tombol item.
        // Kalau item kembali ke tombol inline, layarnya kosong lagi begitu
        // keyboard angka menang di adapter.
        $this->assertSame([], $callbacks, 'Layar ini tidak boleh mengirim tombol item.');
    }

    public function test_layanan_tanpa_paket_tidak_ditampilkan(): void
    {
        // 2 terikat paket, 1 tidak — mengikuti storefront.
        $this->seedCatalog(3, 2);

        $text = (string) $this->screen('mlbb')['text'];

        $this->assertStringContainsString('1 Diamond', $text);
        $this->assertStringContainsString('2 Diamond', $text);
        $this->assertStringNotContainsString('3 Diamond', $text);
    }

    // ------------------------------------------------------------------ nomor

    public function test_nomor_menunjuk_layanan_dan_mengarah_ke_metode_bayar(): void
    {
        $this->seedCatalog(3, 3);

        $entries = (array) ($this->screen('mlbb')['numeric_menu']['entries'] ?? []);

        $this->assertArrayHasKey('1', $entries);
        $this->assertSame('metode', explode(' ', (string) $entries['1']['command'])[0]);
        $this->assertArrayHasKey('0', $entries);
        $this->assertSame('kategori top-up-games', (string) $entries['0']['command']);
    }

    public function test_nomor_halaman_kedua_lanjut_dari_halaman_pertama(): void
    {
        // 12 layanan, 10 per halaman.
        $this->seedCatalog(12, 12);

        $entries = (array) ($this->screen('mlbb', 2)['numeric_menu']['entries'] ?? []);

        // Halaman 2 mulai dari 11 — nomor memakai posisi ABSOLUT, bukan 1 lagi.
        $this->assertArrayHasKey('11', $entries);
        $this->assertArrayHasKey('12', $entries);
        $this->assertArrayNotHasKey('1', $entries);
        $this->assertArrayHasKey('98', $entries);
        $this->assertSame('layanan mlbb page:1', (string) $entries['98']['command']);
    }

    public function test_sepuluh_layanan_per_halaman(): void
    {
        $this->seedCatalog(25, 25);

        $entries = (array) ($this->screen('mlbb')['numeric_menu']['entries'] ?? []);

        $this->assertArrayHasKey('10', $entries);
        $this->assertArrayNotHasKey('11', $entries);
        $this->assertArrayHasKey('99', $entries);
        $this->assertSame('layanan mlbb page:2', (string) $entries['99']['command']);
    }

    public function test_halaman_lebih_dari_satu_mengirim_tombol_pindah(): void
    {
        $this->seedCatalog(12, 12);

        $screen = $this->screen('mlbb');
        $row = (array) (($screen['buttons'] ?? [])[0] ?? []);

        $this->assertNotSame([], $row, 'Tanpa tombol ini halaman kedua hanya bisa dicapai dengan mengetik 99.');

        $button = $row[0];

        // `callback` memakai pola `menu page:` supaya adapter mengenalinya
        // sebagai tombol navigasi dan mengirim pesan kedua.
        $this->assertSame('menu page:2', (string) $button['callback']);

        // Tapi yang dipakai adapter sebagai isi tombol adalah `page_command`:
        // perintah layar layanan, bukan menu. Kalau `callback` yang dikirim,
        // menekan “Next” membuka MENU UTAMA halaman 2.
        $this->assertSame('layanan mlbb page:2', (string) $button['page_command']);
    }

    // ------------------------------------------------------- pin paket spesial

    public function test_paket_spesial_dipin_di_atas_walau_harganya_lebih_mahal(): void
    {
        [$category] = $this->seedCatalog(3, null);

        // Paket murah dibuat LEBIH DULU supaya urutan id-nya lebih kecil: tanpa
        // pin, dia yang muncul di atas.
        $murah = Paket::query()->create(['nama' => '⚡ Proses Instant']);
        $mahal = Paket::query()->create(['nama' => '⭐ Spesial Items']);

        $items = Layanan::query()->where('kategori_id', $category->id)->orderBy('id')->get();
        $murah->layanan()->attach($items[0]->id, ['product_logo' => null]);
        $mahal->layanan()->attach($items[2]->id, ['product_logo' => null]);
        $mahal->layanan()->attach($items[1]->id, ['product_logo' => null]);

        $text = (string) $this->screen('mlbb')['text'];

        // Isi paket spesial harus mendahului isi paket murah — di dalam satu
        // paket urutannya tetap harga menaik, jadi 2 Diamond (Rp 2.000) lebih
        // dulu daripada 3 Diamond (Rp 3.000), dan 1 Diamond yang murah tapi
        // ada di paket biasa justru paling akhir.
        $this->assertStringContainsString('[1]. 2 Diamond', $text);
        $this->assertStringContainsString('[2]. 3 Diamond', $text);
        $this->assertStringContainsString('[3]. 1 Diamond', $text);
        $this->assertLessThan(
            strpos($text, '1 Diamond — Rp 1.000'),
            strpos($text, '3 Diamond — Rp 3.000'),
            'Item paket spesial harus muncul sebelum item paket biasa.',
        );
    }

    public function test_layanan_yang_dipakai_dua_paket_hanya_muncul_sekali(): void
    {
        [$category] = $this->seedCatalog(2, null);

        $items = Layanan::query()->where('kategori_id', $category->id)->orderBy('id')->get();
        foreach (['⭐ Spesial Items', '⚡ Proses Instant'] as $nama) {
            $paket = Paket::query()->create(['nama' => $nama]);
            $paket->layanan()->attach($items[0]->id, ['product_logo' => null]);
        }

        $text = (string) $this->screen('mlbb')['text'];

        $this->assertSame(1, substr_count($text, '1 Diamond'));
    }

    // -------------------------------------------- kategori tanpa layanan berpaket

    public function test_kategori_tanpa_layanan_berpaket_tidak_dipajang(): void
    {
        $this->seedCatalog(3, null, '⚡ Proses Instant', 'mlbb');

        // Kategori lain di tipe yang sama, SEMUA layanannya tanpa paket.
        $type = CategoryType::query()->where('slug', 'top-up-games')->firstOrFail();
        $lain = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'undawn',
            'nama' => 'Undawn',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $lain->id,
            'layanan' => 'Item Undawn',
            'harga_member' => 5000,
            'status' => 'available',
        ]);

        Cache::flush();
        $catalog = app(GatewayCatalogService::class);

        // Untuk bot: kategori itu hilang.
        $kodeBot = array_column($catalog->categories(null, ['type' => 'top-up-games'], true)['data'], 'code');
        $this->assertNotContains('undawn', $kodeBot);

        // Untuk web: tetap ada — web merender tombolnya dari semua layanan.
        $kodeWeb = array_column($catalog->categories(null, ['type' => 'top-up-games'])['data'], 'code');
        $this->assertContains('undawn', $kodeWeb);
    }

    public function test_menu_utama_menyembunyikan_tipe_yang_isinya_hanya_tanpa_paket(): void
    {
        $this->seedCatalog(2, 2, '⚡ Proses Instant', 'mlbb');

        // Tipe lain yang SEMUA kategorinya tanpa paket.
        $tipeLain = CategoryType::query()->create([
            'name' => 'Pulsa & Data', 'slug' => 'pulsa-data', 'sort' => 2,
        ]);
        $pulsa = Kategori::factory()->create([
            'category_type_id' => $tipeLain->id,
            'kode' => 'pulsa-telkomsel',
            'nama' => 'TELKOMSEL',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $pulsa->id,
            'layanan' => 'Telkomsel 50.000',
            'harga_member' => 49000,
            'status' => 'available',
        ]);

        Cache::flush();
        $catalog = app(GatewayCatalogService::class);

        $slugBot = array_column($catalog->categoryTypes([], true)['data'], 'slug');
        $this->assertContains('top-up-games', $slugBot);
        $this->assertNotContains('pulsa-data', $slugBot);

        // Web tetap melihat keduanya.
        $slugWeb = array_column($catalog->categoryTypes()['data'], 'slug');
        $this->assertContains('pulsa-data', $slugWeb);
    }

    // ---------------------------------------------------------------- WhatsApp

    public function test_whatsapp_tidak_ikut_berubah(): void
    {
        $this->seedCatalog(3, 3);
        $catalog = app(GatewayCatalogService::class);

        $wa = app(BotMessageFormatter::class)->formatServices(
            $catalog->services('mlbb'),
            1,
            BotGatewayCapabilities::forSource(self::WA),
            [],
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
        $this->assertStringNotContainsString('[1].', (string) $wa['text']);
    }
}
