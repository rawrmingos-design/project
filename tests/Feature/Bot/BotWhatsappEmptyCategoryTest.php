<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Models\PaketLayanan;
use App\Services\Bot\BotCommandHandler;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Settings\DatabaseSettingsBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bot WhatsApp tidak boleh menampilkan produk yang membuka layar KOSONG.
 *
 * Kenapa test ini ada:
 * Telegram sudah disaring dengan kriteria "punya paket" (`eligibleOnly`), karena
 * Telegram memesan lewat paket. WhatsApp memesan per LAYANAN, jadi kriteria itu
 * SALAH untuk WA — memakainya akan menyembunyikan produk sah (mis. Alight
 * Motion: 1 layanan, nol paket, dan terbukti dipesan lewat bot).
 *
 * Yang dibuang di WA hanya entitas dengan NOL layanan tersedia, karena
 * tombolnya membalas "Produk tidak ditemukan atau belum ada layanan." Terukur
 * di staging: 12 kategori nol-layanan tampil di WA, termasuk satu tipe utuh
 * (`specialist-mobile-legends`) yang isinya 0 layanan.
 *
 * Test ini MENGUNCI DUA ARAH sekaligus:
 *  - kategori nol layanan tidak lagi tampil (memperbaiki dead-end), DAN
 *  - kategori berlayanan-tanpa-paket TETAP tampil (mencegah penyaring yang
 *    terlalu ketat menghapus produk yang bisa dipesan).
 */
class BotWhatsappEmptyCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(DatabaseSettingsBridge::class)->apply();
    }

    private function makeType(string $slug, string $name, int $sort = 1): CategoryType
    {
        return CategoryType::query()->create([
            'name' => $name,
            'slug' => $slug,
            'sort' => $sort,
        ]);
    }

    public function test_kategori_nol_layanan_tidak_tampil_di_whatsapp(): void
    {
        $type = $this->makeType('top-up-games', 'Top Up Games');

        // Punya layanan → harus tampil.
        $sellable = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'free-fire',
            'nama' => 'Free Fire',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $sellable->id,
            'status' => 'available',
        ]);

        // Nol layanan → membuka layar kosong, harus dibuang.
        Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'eggy-party',
            'nama' => 'Eggy Party',
            'status' => 'active',
        ]);

        $catalog = app(GatewayCatalogService::class);

        $wa = $catalog->categories(null, ['type' => 'top-up-games'], false, true);
        $codes = array_column($wa['data'], 'code');

        $this->assertContains('free-fire', $codes);
        $this->assertNotContains('eggy-party', $codes, 'Kategori nol layanan masih tampil di WhatsApp.');

        // Jalur lama (web / pemanggil lain) TIDAK boleh ikut berubah.
        $all = $catalog->categories(null, ['type' => 'top-up-games'], false, false);
        $this->assertContains('eggy-party', array_column($all['data'], 'code'), 'Penyaring WA bocor ke jalur web.');
    }

    public function test_kategori_tanpa_paket_tetap_tampil_di_whatsapp(): void
    {
        // Anti-regresi paling penting: "punya layanan tapi nol paket" adalah
        // produk SAH di WhatsApp. Kalau seseorang mengganti penyaring WA menjadi
        // `eligibleOnly`, test ini merah.
        $type = $this->makeType('app-premium', 'App Premium');

        $noPackage = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'alight-motion-vip',
            'nama' => 'Alight Motion',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $noPackage->id,
            'status' => 'available',
        ]);

        $wa = app(GatewayCatalogService::class)->categories(null, ['type' => 'app-premium'], false, true);

        $this->assertContains(
            'alight-motion-vip',
            array_column($wa['data'], 'code'),
            'Produk berlayanan tanpa paket ikut terhapus — penyaring WA terlalu ketat.',
        );
    }

    public function test_layanan_non_available_tidak_dihitung_sebagai_isi(): void
    {
        // Kriteria penyaring harus SAMA dengan penghitungnya. Kategori yang
        // layanannya semua non-available adalah kategori kosong bagi user.
        $type = $this->makeType('pulsa-data', 'Pulsa & Data');

        $soldOut = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'telkomsel',
            'nama' => 'Telkomsel',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $soldOut->id,
            'status' => 'empty',
        ]);

        $wa = app(GatewayCatalogService::class)->categories(null, ['type' => 'pulsa-data'], false, true);

        $this->assertNotContains(
            'telkomsel',
            array_column($wa['data'], 'code'),
            'Kategori tanpa layanan available dianggap berisi — daftar dan hitungan tidak sinkron.',
        );
    }

    public function test_tipe_tanpa_layanan_dibuang_dari_menu_utama_whatsapp(): void
    {
        $alive = $this->makeType('top-up-games', 'Top Up Games', 1);
        $dead = $this->makeType('specialist-mobile-legends', 'Specialist MLBB', 2);

        $fireFire = Kategori::factory()->create([
            'category_type_id' => $alive->id,
            'kode' => 'free-fire',
            'nama' => 'Free Fire',
            'status' => 'active',
        ]);
        Kategori::factory()->create([
            'category_type_id' => $dead->id,
            'kode' => 'joki-rank-ecer',
            'nama' => 'Joki Rank Ecer',
            'status' => 'active',
        ]);

        // Hanya tipe pertama yang punya layanan tersedia.
        Layanan::factory()->create([
            'kategori_id' => $fireFire->id,
            'status' => 'available',
        ]);

        $menu = app(BotCommandHandler::class)->handle('menu', [], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:628123456789',
            'message_id' => 'whatsapp:test',
            'whatsapp' => '628123456789',
        ]);

        $labels = [];
        foreach (($menu['buttons'] ?? []) as $row) {
            if (isset($row['text'])) {
                $labels[] = $row['text'];
            } elseif (is_array($row)) {
                foreach ($row as $button) {
                    $labels[] = (string) ($button['text'] ?? '');
                }
            }
        }

        $joined = implode(' | ', $labels);
        $this->assertStringContainsString('Top Up Games', $joined);
        $this->assertStringNotContainsString(
            'Specialist',
            $joined,
            'Tipe tanpa satu pun layanan masih tampil di Menu Utama WhatsApp.',
        );
    }

    public function test_telegram_tetap_memakai_kriteria_paket(): void
    {
        // Kontra-test: penyaring WA tidak boleh menggantikan kriteria Telegram.
        $type = $this->makeType('app-premium', 'App Premium');

        $noPackage = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'alight-motion-vip',
            'nama' => 'Alight Motion',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $noPackage->id,
            'status' => 'available',
        ]);

        $catalog = app(GatewayCatalogService::class);

        // Telegram: tanpa paket → TIDAK eligible → tersembunyi (perilaku lama).
        $tg = $catalog->categories(null, ['type' => 'app-premium'], true, false);
        $this->assertNotContains(
            'alight-motion-vip',
            array_column($tg['data'], 'code'),
            'Telegram seharusnya tetap menyaring kategori tanpa paket.',
        );
    }

    public function test_whatsapp_dan_telegram_tidak_saling_menimpa_cache(): void
    {
        // Kedua varian punya kunci cache sendiri. Kalau kuncinya bertabrakan,
        // hasil penyaring WA bocor ke Telegram (atau sebaliknya) selama 300 detik.
        $type = $this->makeType('app-premium', 'App Premium');

        $category = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'alight-motion-vip',
            'nama' => 'Alight Motion',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'status' => 'available',
        ]);

        $catalog = app(GatewayCatalogService::class);

        $wa = array_column($catalog->categories(null, ['type' => 'app-premium'], false, true)['data'], 'code');
        $tg = array_column($catalog->categories(null, ['type' => 'app-premium'], true, false)['data'], 'code');
        $waAgain = array_column($catalog->categories(null, ['type' => 'app-premium'], false, true)['data'], 'code');

        $this->assertContains('alight-motion-vip', $wa);
        $this->assertNotContains('alight-motion-vip', $tg);
        $this->assertSame($wa, $waAgain, 'Cache WA/TG bertabrakan — hasil saling menimpa.');
    }
}
