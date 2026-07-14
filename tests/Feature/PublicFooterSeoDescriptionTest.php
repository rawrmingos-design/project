<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Services\PublicSiteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFooterSeoDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_site_config_keeps_meta_description_plain_and_footer_html_sanitized(): void
    {
        $this->createSettings([
            'deskripsi_web' => '<h2>Top Up Game Murah</h2><p>Proses <strong>cepat</strong> dan aman.</p><p onclick="alert(1)">Promo setiap hari.</p><script>alert("xss")</script>',
        ]);

        $props = app(PublicSiteConfigService::class)->sharedProps();

        $description = $props['siteConfig']['description'];
        $footerHtml = $props['siteConfig']['footerDescriptionHtml'];

        $this->assertStringContainsString('Top Up Game Murah', $description);
        $this->assertStringContainsString('Proses cepat dan aman.', $description);
        $this->assertStringContainsString('Promo setiap hari.', $description);
        $this->assertStringNotContainsString('<', $description);
        $this->assertStringNotContainsString('onclick', $description);
        $this->assertStringNotContainsString('script', $description);

        $this->assertStringContainsString('<strong>cepat</strong>', $footerHtml);
        $this->assertStringNotContainsString('onclick', $footerHtml);
        $this->assertStringNotContainsString('<script', $footerHtml);
    }

    public function test_blade_footer_renders_sanitized_rich_description_with_expand_control(): void
    {
        $response = $this->view('footer', [
            'config' => (object) $this->settingsAttributes([
                'deskripsi_web' => '<p>Top up <strong>cepat</strong> dan aman.</p><p><a href="https://example.com" onclick="alert(1)">Promo game</a></p><script>alert("xss")</script>',
                'logo_footer' => 'assets/logo/footer.webp',
            ]),
        ]);

        $response->assertSee('data-footer-seo', false);
        $response->assertSee('<strong>cepat</strong>', false);
        $response->assertSee('Baca selengkapnya', false);
        $response->assertDontSee('onclick', false);
        $response->assertDontSee('alert("xss")', false);
    }

    public function test_legacy_template_meta_description_strips_rich_footer_html(): void
    {
        $response = $this->view('template.template', [
            'config' => (object) $this->settingsAttributes([
                'deskripsi_web' => '<p>Top Up <strong>Game</strong> Cepat</p><p>dan aman</p><script>alert("xss")</script>',
            ]),
        ]);

        $response->assertSee('name="description" content="Top Up Game Cepat dan aman"', false);
        $response->assertSee('property="og:description" content="Top Up Game Cepat dan aman"', false);
        $response->assertDontSee('content="<p>Top Up', false);
        $response->assertDontSee('&lt;p&gt;Top Up', false);
        $response->assertDontSee('alert(&quot;xss&quot;)', false);
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create($this->settingsAttributes($overrides));
    }

    private function settingsAttributes(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'judul_web' => 'Egy Market',
            'deskripsi_web' => 'Top up game cepat dan aman.',
            'keywords' => 'topup,game',
            'logo_header' => 'assets/logo/header.webp',
            'logo_footer' => 'assets/logo/footer.webp',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#f97316',
            'warna4' => '#444444',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
            'public_theme' => 'default',
            'home_popup_enabled' => true,
            'live_sales_enabled' => true,
            'captcha_enabled' => true,
            'captcha_bypass' => false,
        ], $overrides);
    }
}
