<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi styling pagination halaman artikel (theme legacy).
 *
 * Keluhan client (prod, /id/artikel/): tombol halaman 2-6 tampil sebagai kotak
 * putih kosong — teksnya hanya terlihat setelah di-hover.
 *
 * Akar masalah: theme legacy TIDAK memuat output Tailwind lengkap (hanya
 * 4 stylesheet portabel). View pagination bawaan Laravel
 * (`pagination::tailwind`) memakai ~20 utility yang tidak ada di stylesheet
 * legacy, termasuk `text-gray-700` dan `border-gray-300`. `bg-white` kebetulan
 * terdefinisi, sedangkan warnanya tidak → teks putih di atas putih.
 *
 * Test ini mengunci perbaikan pada level markup:
 *   1. Pagination tidak lagi memakai kelas Tailwind yang hilang.
 *   2. Tombol halaman memakai kelas sendiri (.legacy-pagination__*) yang
 *      didefinisikan di public/assets/css/legacy-pagination.css.
 *   3. CSS-nya benar-benar menetapkan background transparan dan tetap
 *      didefinisikan lewat border, sesuai permintaan client.
 */
class LegacyPaginationStylingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->createSettings(['public_theme' => 'default']);
    }

    public function test_legacy_article_list_pagination_avoids_purged_tailwind_utilities(): void
    {
        $this->createArticles(12);

        $response = $this->get('/id/artikel');

        $response->assertOk();

        $html = $response->getContent();

        // Kelas inilah yang hilang dari stylesheet legacy dan membuat teks
        // tidak terbaca. Tidak boleh dipakai lagi oleh markup pagination.
        foreach (['bg-white', 'bg-gray-200', 'text-gray-700', 'text-gray-600', 'text-gray-800', 'border-gray-300'] as $purged) {
            $this->assertStringNotContainsString(
                $purged,
                $this->extractPaginationHtml($html),
                "Markup pagination theme legacy masih memakai kelas Tailwind '{$purged}' yang tidak ada di stylesheet legacy.",
            );
        }
    }

    public function test_legacy_article_list_pagination_uses_its_own_scoped_classes(): void
    {
        // 27 artikel / 9 per halaman = 3 halaman, sehingga halaman 2 punya
        // prev DAN next sebagai link aktif (di halaman 1 prev disabled,
        // di halaman terakhir next disabled).
        $this->createArticles(27);

        $html = $this->get('/id/artikel?page=2')->assertOk()->getContent();
        $pagination = $this->extractPaginationHtml($html);

        $this->assertStringContainsString('legacy-pagination', $pagination, 'Pagination theme legacy tidak memakai view kustom.');
        $this->assertStringContainsString('legacy-pagination__item', $pagination, 'Tombol halaman tidak memakai kelas scoped.');
        // Navigasi prev/next tetap harus ada.
        $this->assertStringContainsString('rel="prev"', $pagination);
        $this->assertStringContainsString('rel="next"', $pagination);
    }

    public function test_legacy_pagination_stylesheet_declares_transparent_background(): void
    {
        $path = public_path('assets/css/legacy-pagination.css');

        $this->assertFileExists($path, 'Stylesheet pagination theme legacy tidak ikut ter-build.');

        $css = (string) file_get_contents($path);

        // Permintaan client: background transparan, border tetap.
        $this->assertStringContainsString('background-color: transparent', $css);
        $this->assertStringContainsString('border: 1px solid rgba(255, 255, 255, 0.16)', $css);
        $this->assertStringNotContainsString('background-color: #fff', strtolower($css));
    }

    public function test_legacy_template_links_the_pagination_stylesheet(): void
    {
        $this->createArticles(12);

        $html = $this->get('/id/artikel')->assertOk()->getContent();

        $this->assertStringContainsString(
            'assets/css/legacy-pagination.css',
            $html,
            'Template theme legacy tidak me-link stylesheet pagination.',
        );
    }

    public function test_legacy_pagination_renders_the_windowed_pages_not_every_page(): void
    {
        // UrlWindow::get() memakai small slider (SEMUA halaman, tanpa pemisah)
        // selama lastPage < onEachSide * 2 + 8. Dengan onEachSide default 3,
        // batasnya 14 halaman. Jadi untuk benar-benar menguji jalur jendela +
        // pemisah "..." kita perlu lebih dari 14 halaman:
        // 180 artikel / 9 per halaman = 20 halaman, halaman aktif 10.
        $this->createArticles(180);

        $paginator = \App\Models\Artikel::query()
            ->where('status', 'active')
            ->orderByDesc('created_at')
            ->paginate(9, ['*'], 'page', 10);

        $this->assertGreaterThan(14, $paginator->lastPage(), 'Prasyarat test: butuh lebih dari 14 halaman agar pemisah muncul.');

        // Nomor halaman yang HARUS tampil menurut paginator. `elements()`
        // bersifat protected (dipakai internal saat Laravel merender view),
        // jadi diakses lewat closure yang di-bind — sumber data yang sama
        // dengan $elements yang diterima view.
        $elements = (function () {
            return $this->elements();
        })->call($paginator);

        $expectedPages = [];
        $expectedGaps = 0;
        foreach ($elements as $element) {
            if (is_string($element)) {
                $expectedGaps++;
                continue;
            }
            foreach (array_keys($element) as $page) {
                $expectedPages[] = (string) $page;
            }
        }

        $this->assertNotEmpty($expectedPages, 'Prasyarat test tidak terpenuhi: paginator tidak menghasilkan halaman.');
        $this->assertGreaterThan(0, $expectedGaps, 'Prasyarat test tidak terpenuhi: seharusnya ada pemisah halaman di 20 halaman.');
        // Jendela tidak boleh menampilkan semua halaman.
        $this->assertLessThan(
            $paginator->lastPage(),
            count($expectedPages),
            'Prasyarat test tidak terpenuhi: jendela halaman seharusnya tidak menampilkan semua halaman.',
        );

        $html = $this->get('/id/artikel?page=10')->assertOk()->getContent();
        $pagination = $this->extractPaginationHtml($html);

        // Setiap nomor halaman yang diharapkan muncul sebagai tombol/link.
        foreach ($expectedPages as $page) {
            $this->assertMatchesRegularExpression(
                '/class="legacy-pagination__item[^"]*"[^>]*>\s*' . preg_quote($page, '/') . '\s*</',
                $pagination,
                "Nomor halaman {$page} tidak dirender oleh view pagination legacy.",
            );
        }

        // Tidak boleh menampilkan halaman di luar jendela paginator.
        $rendered = [];
        if (preg_match_all('/class="legacy-pagination__item[^"]*"[^>]*>\s*(\d{1,3})\s*</', $pagination, $m)) {
            $rendered = array_values(array_unique($m[1]));
        }
        $this->assertEqualsCanonicalizing(
            $expectedPages,
            $rendered,
            'View pagination legacy menampilkan nomor halaman yang berbeda dari jendela paginator.',
        );

        // Pemisah "..." muncul sesuai jumlah gap dari paginator.
        if ($expectedGaps > 0) {
            $this->assertSame(
                $expectedGaps,
                substr_count($pagination, 'is-gap'),
                'Jumlah pemisah halaman tidak sesuai dengan paginator.',
            );
        }

        $this->assertStringContainsString('is-active', $pagination, 'Halaman aktif tidak ditandai.');
    }

    /**
     * Ambil hanya blok <nav> pagination, supaya assert tidak tertipu oleh
     * markup lain di halaman yang mungkin memang memakai bg-white.
     */
    private function extractPaginationHtml(string $html): string
    {
        if (preg_match('/<nav[^>]*aria-label="Pagination Navigation".*?<\/nav>/s', $html, $matches)) {
            return $matches[0];
        }

        $this->fail('Blok navigasi pagination tidak ditemukan pada halaman artikel theme legacy.');
    }

    private function createArticles(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Artikel::query()->create([
                'title' => "Artikel Pagination {$i}",
                'slug' => "artikel-pagination-{$i}",
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
