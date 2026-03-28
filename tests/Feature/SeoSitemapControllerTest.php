<?php

namespace Tests\Feature;

use App\Models\Artikel;
use App\Models\Kategori;
use App\Models\MediaAsset;
use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeoSitemapControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_custom_sitemap_index_with_foreign_host_falls_back_to_dynamic_index(): void
    {
        $indexAsset = $this->createXmlAsset('sitemap-index-foreign.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <sitemap>
    <loc>https://evil.example.com/sitemap-main.xml</loc>
    <lastmod>2026-03-28T00:00:00+07:00</lastmod>
  </sitemap>
</sitemapindex>
XML);

        $this->createSettings([
            'seo_sitemap_enabled' => true,
            'seo_sitemap_mode' => 'custom_upload',
            'seo_sitemap_index_asset_id' => $indexAsset->id,
            'seo_sitemap_include_categories' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertSee('/sitemap-main.xml', false);
        $response->assertDontSee('evil.example.com', false);
    }

    public function test_custom_categories_sitemap_with_admin_path_falls_back_to_dynamic_categories(): void
    {
        $this->createActiveCategory('Mobile Legends', 'mobile-legends', 'mlbb');

        $categoriesAsset = $this->createXmlAsset('sitemap-categories-admin.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>http://localhost/admin/settings</loc>
    <lastmod>2026-03-28</lastmod>
    <changefreq>daily</changefreq>
    <priority>0.9</priority>
  </url>
</urlset>
XML);

        $this->createSettings([
            'seo_sitemap_enabled' => true,
            'seo_sitemap_mode' => 'custom_upload',
            'seo_sitemap_include_categories' => true,
            'seo_sitemap_categories_asset_id' => $categoriesAsset->id,
        ]);

        $response = $this->get('/sitemap-categories.xml');

        $response->assertOk();
        $response->assertSee('/id/mlbb', false);
        $response->assertDontSee('/admin/settings', false);
    }

    public function test_custom_mode_without_assets_falls_back_to_dynamic_main_sitemap(): void
    {
        Artikel::query()->create([
            'slug' => 'promo-weekly-pass',
            'title' => 'Promo Weekly Pass',
            'status' => 'active',
            'layout' => 'default',
            'views' => 0,
        ]);

        $this->createSettings([
            'seo_sitemap_enabled' => true,
            'seo_sitemap_mode' => 'custom_upload',
            'seo_sitemap_include_articles' => true,
            'seo_sitemap_main_asset_id' => null,
        ]);

        $response = $this->get('/sitemap-main.xml');

        $response->assertOk();
        $response->assertSee('/id', false);
        $response->assertSee('/id/artikel/promo-weekly-pass', false);
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        $base = [
            'judul_web' => 'Test Topup',
            'deskripsi_web' => 'Deskripsi test',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
            'seo_robots_enabled' => true,
            'seo_sitemap_enabled' => true,
            'seo_sitemap_include_categories' => true,
            'seo_sitemap_include_articles' => true,
            'seo_sitemap_cache_minutes' => 30,
            'seo_sitemap_mode' => 'dynamic',
        ];

        return SettingWeb::query()->create(array_merge($base, $overrides));
    }

    private function createXmlAsset(string $filename, string $xml): MediaAsset
    {
        $relativePath = '/assets/xml/tests/' . $filename;
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $xml);

        return MediaAsset::query()->create([
            'name' => pathinfo($filename, PATHINFO_FILENAME),
            'folder' => 'xml',
            'path' => $relativePath,
        ]);
    }

    private function createActiveCategory(string $name, string $slug, string $kode): Kategori
    {
        return Kategori::query()->create([
            'nama' => $name,
            'sub_nama' => $slug,
            'kode' => $kode,
            'status' => 'active',
            'tipe' => 'game',
        ]);
    }
}
