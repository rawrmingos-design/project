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

    private array $createdXmlPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        foreach ($this->createdXmlPaths as $path) {
            File::delete($path);
        }

        parent::tearDown();
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
            'seo_sitemap_mode' => 'custom_upload',
            'seo_sitemap_index_asset_id' => $indexAsset->id,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/sitemap-main.xml', false)
            ->assertDontSee('evil.example.com', false);
    }

    public function test_custom_categories_sitemap_with_admin_path_falls_back_to_dynamic_categories(): void
    {
        $this->createActiveCategory('Mobile Legends', 'mobile-legends', 'mlbb');
        $categoriesAsset = $this->createXmlAsset('sitemap-categories-admin.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>http://localhost/admin/settings</loc>
  </url>
</urlset>
XML);

        $this->createSettings([
            'seo_sitemap_mode' => 'custom_upload',
            'seo_sitemap_categories_asset_id' => $categoriesAsset->id,
        ]);

        $this->get('/sitemap-categories.xml')
            ->assertOk()
            ->assertSee('/id/mlbb', false)
            ->assertDontSee('/admin/settings', false);
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
            'seo_sitemap_mode' => 'custom_upload',
            'seo_sitemap_include_articles' => true,
            'seo_sitemap_main_asset_id' => null,
        ]);

        $this->get('/sitemap-main.xml')
            ->assertOk()
            ->assertSee('/id', false)
            ->assertSee('/id/artikel/promo-weekly-pass', false);
    }

    public function test_sitemap_cache_context_changes_when_custom_asset_changes_without_flush(): void
    {
        $baseUrl = rtrim((string) env('MAIN_DOMAIN_URL', config('app.url')), '/');
        $firstAsset = $this->createXmlAsset('sitemap-main-first.xml', <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{$baseUrl}/id/price-list</loc></url>
</urlset>
XML);
        $secondAsset = $this->createXmlAsset('sitemap-main-second.xml', <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>{$baseUrl}/id/reviews</loc></url>
</urlset>
XML);
        $settings = $this->createSettings([
            'seo_sitemap_mode' => 'custom_upload',
            'seo_sitemap_main_asset_id' => $firstAsset->id,
        ]);

        $this->get('/sitemap-main.xml')
            ->assertOk()
            ->assertSee('/id/price-list', false)
            ->assertDontSee('/id/reviews', false);

        $settings->update(['seo_sitemap_main_asset_id' => $secondAsset->id]);

        $this->get('/sitemap-main.xml')
            ->assertOk()
            ->assertSee('/id/reviews', false)
            ->assertDontSee('/id/price-list', false);
    }

    public function test_sitemap_cache_context_changes_when_include_articles_changes(): void
    {
        Artikel::query()->create([
            'slug' => 'cache-context-article',
            'title' => 'Cache Context Article',
            'status' => 'active',
            'layout' => 'default',
            'views' => 0,
        ]);
        $settings = $this->createSettings(['seo_sitemap_include_articles' => true]);

        $this->get('/sitemap-main.xml')
            ->assertOk()
            ->assertSee('/id/artikel/cache-context-article', false);

        $settings->update(['seo_sitemap_include_articles' => false]);

        $this->get('/sitemap-main.xml')
            ->assertOk()
            ->assertDontSee('/id/artikel/cache-context-article', false)
            ->assertSee('/id/price-list', false);
    }

    public function test_dynamic_sitemap_locations_are_final_public_routes_for_both_themes(): void
    {
        $this->withoutVite();
        $this->withViewErrors([]);
        $this->createActiveCategory('Mobile Legends', 'mobile-legends', 'mlbb');
        Artikel::query()->create([
            'slug' => 'promo-weekly-pass',
            'title' => 'Promo Weekly Pass',
            'status' => 'active',
            'layout' => 'default',
            'views' => 0,
        ]);
        $settings = $this->createSettings(['public_theme' => 'bangjeff']);

        foreach (['bangjeff', 'default'] as $theme) {
            $settings->update(['public_theme' => $theme]);
            Cache::flush();

            $mainXml = $this->get('/sitemap-main.xml')->assertOk()->getContent();
            $categoryXml = $this->get('/sitemap-categories.xml')->assertOk()->getContent();
            $locations = array_merge($this->extractSitemapLocations($mainXml), $this->extractSitemapLocations($categoryXml));

            $this->assertNotEmpty($locations);

            foreach ($locations as $location) {
                $path = (string) parse_url($location, PHP_URL_PATH);
                $this->assertNotSame('', $path);
                if ($path === '/id/invoices') {
                    $response = $this->get($path);
                    $this->assertSame(200, $response->status(), "Sitemap location failed: {$location}");
                    continue;
                }

                $response = $this->get($path);
                $this->assertSame(200, $response->status(), "Sitemap location failed: {$location}");
            }
        }
    }

    private function extractSitemapLocations(string $xml): array
    {
        preg_match_all('/<loc>(.*?)<\/loc>/s', $xml, $matches);

        return array_values(array_filter(array_map(
            static fn (string $location): string => html_entity_decode(trim($location)),
            $matches[1] ?? [],
        )));
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
            'public_theme' => 'default',
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
        $filename = 'runtime-' . bin2hex(random_bytes(8)) . '-' . ltrim($filename, '/');
        $relativePath = '/assets/xml/tests/' . $filename;
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $xml);
        $this->createdXmlPaths[] = $absolutePath;

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
