<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\SettingWeb;
use App\Services\MediaAssetDedupeService;
use App\Services\MediaAssetDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Banner Menu Utama bot (`setting_webs.bot_menu_banner`) adalah kolom gambar
 * BARU, dan kolom gambar punya KEWAJIBAN di luar sekadar tampil: harus
 * terdaftar sebagai referensi di setiap tempat yang menentukan nasib berkasnya.
 *
 * Tiga tempat itu, dan akibat nyata kalau terlewat -- semuanya SENYAP, tidak
 * ada error yang muncul:
 *
 * 1. `MediaAssetDedupeService::legacyReferences()` -- dedupe memakai daftar ini
 *    untuk menentukan path mana yang terlindungi. Tidak terdaftar = dedupe
 *    menganggap bannernya sampah, MENGHAPUS berkasnya, sementara kolomnya masih
 *    menunjuk ke sana. Banner hilang sendiri. Dedupe sudah LIVE di prod.
 * 2. `MediaAssetDeletionService::references()` -- daftar ini dipakai untuk
 *    mengosongkan referensi sebelum berkas dihapus. Tidak terdaftar = kolom
 *    menggantung (dead reference) setelah berkasnya hilang.
 * 3. `RepairMissingImageRecords` -- tidak terdaftar = banner rusak tidak pernah
 *    muncul di laporan perbaikan.
 *
 * Test ini mengukur ketiganya lewat perilaku nyata (jalankan dedupe, hitung
 * referensi), bukan dengan memeriksa isi array konstanta.
 */
class BotMenuBannerMediaReferenceTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            File::delete($path);
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    private function writeFile(string $relativePath, string $contents): void
    {
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $contents);
        $this->tempFiles[] = $absolutePath;
    }

    private function makeAsset(string $relativePath): MediaAsset
    {
        return MediaAsset::query()->create([
            'name' => pathinfo($relativePath, PATHINFO_FILENAME),
            'folder' => 'bot',
            'path' => $relativePath,
        ]);
    }

    private function createSettings(string $banner): SettingWeb
    {
        return SettingWeb::query()->create([
            'id' => 1, 'judul_web' => 'Test Topup', 'deskripsi_web' => 'd',
            'keywords' => 'k', 'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/x', 'url_tiktok' => 'https://tiktok.com/@x',
            'url_youtube' => 'https://youtube.com/@x', 'url_fb' => 'https://facebook.com/x',
            'topupindo_api' => 'a', 'warna1' => '#111111', 'warna2' => '#222222',
            'warna3' => '#333333', 'warna4' => '#444444',
            'paydisini_apikey' => 'p', 'order_prefik' => 'INV', 'public_theme' => 'default',
            'bot_menu_banner' => $banner,
        ]);
    }

    /**
     * Yang paling berbahaya: dedupe LIVE di prod, dan dia menghapus berkas
     * SALINAN. Kalau banner tidak terlindungi, salinan yang dipakai bot bisa
     * yang dihapus.
     */
    public function test_dedupe_tidak_menghapus_berkas_banner_yang_dipakai(): void
    {
        $payload = 'banner-content-' . Str::random(10);
        $referenced = '/assets/bot/menu-banner.webp';
        $duplicate = '/assets/media/9101/menu-banner-copy.webp';

        $this->writeFile($referenced, $payload);
        $this->writeFile($duplicate, $payload);

        // URUTAN PENTING. Kalau tidak ada anggota yang terdeteksi
        // direferensikan, dedupe menyisakan satu baris saja -- yang id-nya
        // terkecil. Dengan mendaftarkan salinan LEBIH DULU, yang tersisa
        // adalah salinannya dan baris banner-lah yang dihapus. Urutan
        // sebaliknya membuat test lolos palsu: banner selamat karena kebetulan
        // id-nya lebih kecil, bukan karena terlindungi.
        $dropRow = $this->makeAsset($duplicate);
        $keepRow = $this->makeAsset($referenced);

        $this->createSettings($referenced);

        $result = app(MediaAssetDedupeService::class)->dedupe(true);

        $this->assertSame(
            1,
            $result['deleted_rows'],
            'Salinan yang tidak dipakai siapa pun memang harus dihapus.'
        );

        // Banner yang dirujuk kolom harus UTUH -- baris maupun berkasnya.
        $this->assertDatabaseHas('media_assets', ['id' => $keepRow->id]);
        $this->assertDatabaseMissing('media_assets', ['id' => $dropRow->id]);
        $this->assertFileExists(
            public_path(ltrim($referenced, '/')),
            'Berkas banner dihapus padahal kolom bot_menu_banner masih menunjuk ke sana.'
        );

        // Dan kolomnya tidak boleh dikosongkan sebagai efek samping.
        $this->assertSame(
            $referenced,
            SettingWeb::query()->findOrFail(1)->bot_menu_banner,
        );
    }

    /**
     * Tanpa entri di `references()`, mengosongkan QR/banner di panel tidak akan
     * membersihkan kolomnya -- jadi setelah berkas dihapus, kolomnya menunjuk
     * ke berkas yang sudah tidak ada.
     */
    public function test_hapus_aset_banner_mengosongkan_kolomnya(): void
    {
        $relative = '/assets/bot/menu-banner.webp';
        $this->writeFile($relative, 'banner-content');
        $asset = $this->makeAsset($relative);
        $this->createSettings($relative);

        $references = app(MediaAssetDeletionService::class)->references($asset);

        $this->assertNotEmpty(
            $references,
            'Kolom bot_menu_banner tidak terdeteksi sebagai referensi aset.'
        );

        $found = collect($references)->firstWhere('column', 'bot_menu_banner');
        $this->assertNotNull($found, 'Referensi harus menyebut kolom bot_menu_banner.');
        $this->assertSame('setting_webs', $found['table']);

        app(MediaAssetDeletionService::class)->delete($asset);

        $this->assertDatabaseHas('setting_webs', ['id' => 1, 'bot_menu_banner' => null]);
    }

    public function test_banner_ikut_dioptimasi(): void
    {
        // Perintah perawatan gambar hanya memproses kolom yang terdaftar; kalau
        // tidak terdaftar, banner tidak pernah mendapat varian WebP-nya.
        $source = $this->imagePathsOf('bot_menu_banner');

        $this->assertTrue(
            $source->valid(),
            'Kolom bot_menu_banner tidak terdaftar di pipeline optimize (imagePaths()).'
        );
    }

    /**
     * Report `media:repair-missing-image-records` adalah satu-satunya daftar
     * gambar rusak yang dilihat admin. Logo header/footer/favicon sudah
     * terdaftar di sana; banner bot harus ikut, kalau tidak banner yang
     * berkasnya hilang tidak pernah muncul di laporan dan tidak ketahuan.
     */
    public function test_banner_rusak_muncul_di_laporan_perbaikan(): void
    {
        // Path menunjuk berkas yang TIDAK ada di disk.
        $this->createSettings('/assets/bot/banner-yang-hilang.webp');

        $command = app(\App\Console\Commands\RepairMissingImageRecords::class);
        $method = new \ReflectionMethod($command, 'buildMissingReport');
        $report = $method->invoke($command);

        $this->assertArrayHasKey(
            'Setting.bot_menu_banner',
            $report['summary'],
            'Banner bot tidak terdaftar di laporan gambar rusak.'
        );
        $this->assertSame(
            1,
            $report['summary']['Setting.bot_menu_banner'],
            'Banner dengan berkas hilang harus terhitung sebagai rusak.'
        );
        $this->assertSame(
            '/assets/bot/banner-yang-hilang.webp',
            $report['details']['Setting.bot_menu_banner'][0]['path'] ?? null,
        );
    }

    /**
     * Ambil daftar path yang benar-benar dikenali `OptimizeExistingImages`
     * dengan menyuntikkan satu baris setting dan membaca generator-nya.
     */
    private function imagePathsOf(string $column): \Generator
    {
        $relative = '/assets/bot/optimize-probe.webp';
        $this->writeFile($relative, 'optimize-content');
        $this->createSettings($relative);

        $command = app(\App\Console\Commands\OptimizeExistingImages::class);
        $method = new \ReflectionMethod($command, 'imagePaths');

        $found = false;
        foreach ($method->invoke($command) as $item) {
            if (str_contains((string) ($item['source'] ?? ''), $column)) {
                $found = true;
                break;
            }
        }

        // Generator: kembalikan generator kosong/berisi sesuai temuan.
        yield from $found ? [['source' => 'setting_webs.' . $column]] : [];
    }
}
