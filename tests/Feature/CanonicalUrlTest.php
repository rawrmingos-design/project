<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\SettingWeb;
use App\Support\CanonicalUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanonicalUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_normalizes_absolute_urls_to_https_non_www_without_query_or_fragment(): void
    {
        $this->get('http://www.istanatopup.test/id?utm_source=google');

        $canonical = CanonicalUrl::normalize('http://www.istanatopup.test/id/mobile-legends?utm_source=google#reviews');

        $this->assertSame('https://istanatopup.test/id/mobile-legends', $canonical);
    }

    public function test_it_normalizes_relative_paths_against_current_request_host(): void
    {
        $this->get('http://www.istanatopup.test/id?utm_source=google');

        $canonical = CanonicalUrl::normalize('/id/mobile-legends?utm_source=google#reviews');

        $this->assertSame('https://istanatopup.test/id/mobile-legends', $canonical);
    }

    public function test_inertia_head_renders_https_non_www_canonical_and_og_url(): void
    {
        $this->withoutVite();
        $this->seedPublicSettings('bangjeff');

        $response = $this->get('http://www.istanatopup.test/id?utm_source=google');

        $response
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://istanatopup.test/id">', false)
            ->assertSee('<meta property="og:url" content="https://istanatopup.test/id">', false);
    }

    public function test_legacy_template_renders_https_non_www_canonical_and_og_url(): void
    {
        $this->withoutVite();
        $this->seedPublicSettings('default');

        $response = $this->get('http://www.istanatopup.test/id?utm_source=google');

        $response
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://istanatopup.test/id">', false)
            ->assertSee('<meta property="og:url" content="https://istanatopup.test/id">', false);
    }

    private function seedPublicSettings(string $theme): void
    {
        SettingWeb::create([
            'id' => 1,
            'judul_web' => 'Istana Topup',
            'deskripsi_web' => 'Demo storefront',
            'keywords' => 'top up game',
            'logo_header' => 'assets/logo/logo.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/6281234567890',
            'url_ig' => 'https://instagram.com/istanatopup',
            'url_tiktok' => 'https://tiktok.com/@istanatopup',
            'url_youtube' => 'https://youtube.com/@istanatopup',
            'url_fb' => 'https://facebook.com/istanatopup',
            'topupindo_api' => 'demo-topupindo-key',
            'paydisini_apikey' => 'demo-paydisini-key',
            'order_prefik' => 'IST',
            'warna1' => '#0f172a',
            'warna2' => '#ea580c',
            'warna3' => '#f59e0b',
            'warna4' => '#fb923c',
            'public_theme' => $theme,
        ]);

        Berita::create([
            'tipe' => 'banner',
            'judul' => 'Promo Banner',
            'deskripsi' => 'Banner test',
            'images' => 'assets/banner/test.webp',
        ]);
    }
}
