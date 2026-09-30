<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Geometry kartu artikel pada theme legacy.
 *
 * Keluhan client (prod, /id/artikel): "card pada list artikelnya kurang rapi,
 * mungkin dari segi gambarnya, jangan sampai kena crop tapi tetap sesuai
 * patternnya".
 *
 * Akar masalah — theme legacy TIDAK menjalankan build Tailwind. Markup-nya
 * memakai kelas Tailwind, sementara asetnya berasal dari 5 stylesheet portabel
 * plus beberapa blok <style> inline. Kelas yang tidak ada di stylesheet itu
 * kalah DIAM-DIAM (tanpa error), sehingga:
 *
 *   `aspect-[16/9]`  -> tidak terdefinisi di mana pun. Kotak gambar jatuh ke
 *                       `aspect-ratio: auto` dan tingginya mengikuti rasio ASLI
 *                       tiap file thumbnail. Terukur 50 berkas, dari 515x916
 *                       (0,56) sampai 1600x640 (2,50); tinggi kotak gambar di
 *                       produksi melompat 153px -> 680px dan tinggi kartu
 *                       457 / 742 / 894 px, jadi grid tampak bergerigi.
 *   `line-clamp-2/3` -> judul & ringkasan tidak terpotong (terukur: judul
 *                       dirender 3 baris padahal markup meminta 2), sehingga
 *                       tinggi blok teks ikut tak seragam.
 *   `from-black/80` + `opacity-60` -> gradasi gelap di bawah gambar tidak
 *                       dirender (`background-image: none`) padahal tanggal &
 *                       "Views" berwarna putih di atas gambar.
 *   `bg-cover` + `bg-center` (hero FEATURED) -> `background-size: auto`,
 *                       `background-position: 0% 0%`: gambar unggulan tampil
 *                       mentok di sudut, tidak memenuhi kotak.
 *
 * Perbaikannya memakai kelas ber-prefix `legacy-article-card__*` /
 * `legacy-article-grid` / `legacy-featured-media` yang didefinisikan di
 * public/assets/css/legacy-article-cards.css, mengikuti pola yang sudah dipakai
 * pagination legacy.
 */
class LegacyArticleCardGeometryTest extends TestCase
{
    use RefreshDatabase;

    /** Fixture gambar yang dibuat test ini, dihapus lagi setelah selesai. */
    private const FIXTURES = [
        'assets/articles/portrait.webp',
        'assets/articles/landscape.webp',
    ];

    protected function tearDown(): void
    {
        foreach (self::FIXTURES as $relative) {
            $absolute = public_path($relative);

            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }

        // Buang direktori fixture kalau test ini yang membuatnya, supaya
        // `git status` tetap bersih setelah suite dijalankan.
        $directory = public_path('assets/articles');

        if (is_dir($directory) && count((array) glob($directory . '/*')) === 0) {
            @rmdir($directory);
        }

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->createSettings(['public_theme' => 'default']);
    }

    /**
     * Kartu tidak boleh lagi bergantung pada utility Tailwind yang tidak ada
     * di stylesheet theme legacy.
     */
    public function test_legacy_article_card_avoids_unbuilt_tailwind_geometry_utilities(): void
    {
        $this->createArticles(12);

        $html = $this->get('/id/artikel')->assertOk()->getContent();

        // Kelas-kelas ini terbukti absen dari 5 stylesheet legacy yang dimuat
        // halaman (diverifikasi dengan grep atas aset yang benar-benar di-link)
        // dan membuat geometri kartu tidak terkendali.
        foreach ([
            'aspect-[16/9]' => 'kotak gambar kehilangan rasio tetap dan ikut rasio asli file',
            'line-clamp-2' => 'judul tidak terpotong 2 baris sehingga tinggi kartu tidak seragam',
            'line-clamp-3' => 'ringkasan tidak terpotong 3 baris sehingga tinggi kartu tidak seragam',
            'from-black' => 'gradasi gelap di bawah gambar tidak dirender',
            'bg-gradient-to-t' => 'gradasi gelap di bawah gambar tidak dirender',
            'bg-cover' => 'gambar unggulan tidak memenuhi kotaknya',
            'bg-center' => 'gambar unggulan tidak terpusat',
        ] as $class => $reason) {
            $this->assertStringNotContainsString(
                $class,
                $html,
                "Halaman artikel theme legacy masih memakai kelas Tailwind '{$class}' yang tidak ada di stylesheet legacy: {$reason}.",
            );
        }
    }

    public function test_legacy_article_card_uses_its_own_scoped_classes(): void
    {
        $this->createArticles(12);

        $grid = $this->extractArticleGridHtml($this->get('/id/artikel')->assertOk()->getContent());

        $this->assertStringContainsString(
            'legacy-article-grid',
            $grid,
            'Grid artikel tidak memakai kelas scoped, sehingga item grid bisa melebar mengikuti konten.',
        );
        $this->assertStringContainsString('legacy-article-card__media', $grid, 'Kotak gambar tidak memakai kelas scoped.');
        $this->assertStringContainsString('legacy-article-card__scrim', $grid, 'Gradasi gelap tidak memakai kelas scoped.');
        $this->assertStringContainsString('legacy-article-card__meta', $grid, 'Baris tanggal/views tidak memakai kelas scoped.');
        $this->assertStringContainsString('legacy-article-card__title', $grid, 'Judul tidak memakai kelas scoped.');
        $this->assertStringContainsString('legacy-article-card__excerpt', $grid, 'Ringkasan tidak memakai kelas scoped.');
    }

    /**
     * Gambar unggulan (FEATURED NEWS) memakai kelas lokal, bukan `bg-cover`
     * yang tidak ter-build.
     */
    public function test_legacy_featured_image_uses_its_own_scoped_class(): void
    {
        $this->createArticles(12);

        $html = $this->get('/id/artikel')->assertOk()->getContent();

        $this->assertStringContainsString(
            'legacy-featured-media',
            $html,
            'Gambar unggulan tidak memakai kelas scoped, sehingga tampil mentok di sudut kotak.',
        );
    }

    /**
     * Kontrak CSS: bingkai berasio tetap + `cover` supaya tidak ada gelembung
     * (letterbox) dan tinggi kartu seragam.
     */
    public function test_legacy_article_card_stylesheet_pins_a_fixed_aspect_and_cover(): void
    {
        $path = public_path('assets/css/legacy-article-cards.css');

        $this->assertFileExists($path, 'Stylesheet kartu artikel theme legacy tidak ikut ter-build.');

        $css = (string) file_get_contents($path);
        $declarations = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;

        // Rasio tetap 16:9 pada bingkai gambar, bukan `auto` yang mengikuti
        // rasio asli tiap file.
        $this->assertMatchesRegularExpression(
            '/\.legacy-article-card__media\s*\{[^}]*aspect-ratio:\s*16\s*\/\s*9/s',
            $declarations,
            'Bingkai gambar tidak dipatok ke rasio 16:9 sehingga tingginya mengikuti rasio asli file.',
        );
        $this->assertMatchesRegularExpression(
            '/\.legacy-article-card__media\s*\{[^}]*overflow:\s*hidden/s',
            $declarations,
            'Bingkai gambar tidak memotong luberan, sehingga gambar bisa tumpah keluar kartu.',
        );

        // Gambar harus MENGISI bingkai (cover), bukan menyisakan celah.
        $this->assertMatchesRegularExpression(
            '/\.legacy-article-card__media\s+img\s*\{[^}]*object-fit:\s*cover/s',
            $declarations,
            'Gambar tidak memakai object-fit: cover sehingga bisa tampil gepeng atau menyisakan celah.',
        );
        $this->assertMatchesRegularExpression(
            '/\.legacy-article-card__media\s+img\s*\{[^}]*object-position:\s*center/s',
            $declarations,
            'Gambar tidak diposisikan di tengah, sehingga pemotongan bisa menggeser subjek keluar bingkai.',
        );

        // Judul & ringkasan harus dipotong pada jumlah baris yang pasti.
        $this->assertMatchesRegularExpression(
            '/\.legacy-article-card__title\s*\{[^}]*-webkit-line-clamp:\s*2/s',
            $declarations,
            'Judul tidak dipotong tepat 2 baris sehingga tinggi kartu tidak seragam.',
        );
        $this->assertMatchesRegularExpression(
            '/\.legacy-article-card__excerpt\s*\{[^}]*-webkit-line-clamp:\s*3/s',
            $declarations,
            'Ringkasan tidak dipotong tepat 3 baris sehingga tinggi kartu tidak seragam.',
        );

        // Gradasi gelap harus benar-benar punya latar, bukan dibiarkan kosong.
        $this->assertMatchesRegularExpression(
            '/\.legacy-article-card__scrim\s*\{[^}]*background-image:\s*linear-gradient/s',
            $declarations,
            'Gradasi gelap tidak didefinisikan, sehingga teks putih tanggal/views kehilangan kontras.',
        );

        // Gambar unggulan harus cover + center.
        $this->assertMatchesRegularExpression(
            '/\.legacy-featured-media\s*\{[^}]*background-size:\s*cover/s',
            $declarations,
            'Gambar unggulan tidak dipaksa memenuhi kotaknya.',
        );
        $this->assertMatchesRegularExpression(
            '/\.legacy-featured-media\s*\{[^}]*background-position:\s*center/s',
            $declarations,
            'Gambar unggulan tidak diposisikan di tengah.',
        );
    }

    /**
     * Stylesheet wajib di-link dengan query versi, mengikuti pola
     * `legacy-pagination.css`. Tanpa versi, URL-nya tetap sama antar-deploy
     * sementara Cloudflare menyimpannya di edge 30 hari, sehingga perubahan CSS
     * tidak sampai ke pengunjung.
     */
    public function test_legacy_article_card_stylesheet_is_linked_with_a_version_query(): void
    {
        $this->createArticles(12);

        $html = $this->get('/id/artikel')->assertOk()->getContent();

        $file = 'legacy-article-cards.css';
        $path = public_path('assets/css/' . $file);

        $this->assertFileExists($path, "Stylesheet {$file} tidak ditemukan.");

        $this->assertMatchesRegularExpression(
            '#' . preg_quote($file, '#') . '\?v=' . filemtime($path) . '#',
            $html,
            "Stylesheet {$file} tidak di-link dengan query versi, sehingga perubahan CSS-nya tertahan cache edge Cloudflare.",
        );
    }

    /**
     * Mode tampil gambar harus bisa ditentukan dan ditandai di markup.
     *
     * `fit="auto"` memilih `cover` untuk gambar lanskap dan `contain` untuk
     * gambar potret. Tanpa ini, thumbnail potret (rasio 0,56 pada data
     * produksi) dipaksa `cover` dan kehilangan lebih dari separuh isinya.
     */
    public function test_optimized_image_marks_the_contain_fit_for_portrait_sources(): void
    {
        // Thumbnail produksi yang benar-benar berbentuk potret + satu lanskap.
        $fixtures = [
            ['assets/articles/portrait.webp', 515, 916, 'contain'],
            ['assets/articles/landscape.webp', 1200, 630, 'cover'],
        ];

        foreach ($fixtures as [$relative, $width, $height, $expectedFit]) {
            $absolute = public_path($relative);
            @mkdir(dirname($absolute), 0777, true);

            $canvas = imagecreatetruecolor($width, $height);
            imagefilledrectangle($canvas, 0, 0, $width - 1, $height - 1, imagecolorallocate($canvas, 200, 30, 30));
            imagewebp($canvas, $absolute, 80);
            imagedestroy($canvas);

            $html = $this->blade(
                '<x-optimized-image :src="$src" fit="auto" :fit-min-ratio="1.0" />',
                ['src' => $relative],
            );

            $this->assertStringContainsString(
                'data-fit="' . $expectedFit . '"',
                (string) $html,
                "Gambar {$width}x{$height} ({$relative}) salah mode tampil: harusnya '{$expectedFit}'.",
            );
        }
    }

    /**
     * Nilai `fit` yang eksplisit tidak boleh diubah oleh logika `auto`, supaya
     * kartu produk/banner yang sudah memakai `cover` tidak berubah perilakunya.
     */
    public function test_optimized_image_respects_an_explicit_fit(): void
    {
        $relative = 'assets/articles/portrait.webp';
        $absolute = public_path($relative);
        @mkdir(dirname($absolute), 0777, true);
        $canvas = imagecreatetruecolor(515, 916);
        imagewebp($canvas, $absolute, 80);
        imagedestroy($canvas);

        $html = (string) $this->blade(
            '<x-optimized-image :src="$src" fit="cover" />',
            ['src' => $relative],
        );

        $this->assertStringContainsString('data-fit="cover"', $html);
    }

    /**
     * Kontrak CSS untuk mode `contain`.
     */
    public function test_legacy_article_card_stylesheet_switches_to_contain_when_marked(): void
    {
        $css = (string) file_get_contents(public_path('assets/css/legacy-article-cards.css'));
        $declarations = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;

        $this->assertMatchesRegularExpression(
            '/img\[data-fit="contain"\]\s*\{[^}]*object-fit:\s*contain/s',
            $declarations,
            'Gambar bertanda contain tidak ditampilkan utuh, sehingga thumbnail potret tetap terpotong.',
        );
    }

    /**
     * Ambil hanya blok grid artikel, supaya assert tidak tertipu markup lain.
     */
    private function extractArticleGridHtml(string $html): string
    {
        $start = strpos($html, 'legacy-article-grid');
        $end = strpos($html, 'legacy-pagination');

        $this->assertNotFalse($start, 'Grid artikel tidak dirender pada halaman artikel theme legacy.');
        $this->assertNotFalse($end, 'Blok pagination tidak ditemukan pada halaman artikel theme legacy.');

        return substr($html, $start, $end - $start);
    }

    private function createArticles(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Artikel::query()->create([
                'title' => "Artikel Kartu {$i}",
                'slug' => "artikel-kartu-{$i}",
                'thumbnail' => 'assets/articles/article.webp',
                'content' => '<p>Konten artikel.</p>',
                'meta_description' => 'Deskripsi artikel.',
                'keywords' => 'game,promo',
                'layout' => 'default',
                'status' => 'active',
                'views' => 0,
            ]);
        }
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'judul_web' => 'Test Topup',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'paydisini_apikey' => 'dummy-paydisini',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'order_prefik' => 'INV',
            'public_theme' => 'default',
        ], $overrides));
    }
}
