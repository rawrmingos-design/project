<?php

namespace Tests\Feature;

use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_ga_snippet_is_skipped_when_gtm_is_active(): void
    {
        $settings = $this->createSettings([
            'google_tag_manager_id' => 'GTM-TEST123',
            'google_analytics_id' => 'G-TEST12345',
        ]);

        $response = $this->renderTrackingPage($settings);

        $response->assertOk();
        $response->assertSee("https://www.googletagmanager.com/gtm.js?id='+i+dl", false);
        $response->assertSee("GTM-TEST123", false);
        $response->assertDontSee("googletagmanager.com/gtag/js?id=G-TEST12345", false);
        $response->assertDontSee("gtag('config', 'G-TEST12345')", false);
        $response->assertSee('Direct Google Analytics snippet skipped because GTM is active', false);
    }

    public function test_custom_gtm_snippet_overrides_standard_gtm_and_direct_ga(): void
    {
        $settings = $this->createSettings([
            'google_tag_manager_id' => 'GTM-TEST123',
            'google_analytics_id' => 'G-TEST12345',
            'gtm_custom_head_script' => '<script src="https://gtm.istanatopup.com/custom-loader.js"></script>',
            'gtm_custom_body_noscript' => '<noscript><iframe src="https://gtm.istanatopup.com/ns.html?id=GTM-CUSTOM"></iframe></noscript>',
        ]);

        $response = $this->renderTrackingPage($settings);

        $response->assertOk();
        $response->assertSee('https://gtm.istanatopup.com/custom-loader.js', false);
        $response->assertSee('https://gtm.istanatopup.com/ns.html?id=GTM-CUSTOM', false);
        $response->assertDontSee("https://www.googletagmanager.com/gtm.js?id='+i+dl", false);
        $response->assertDontSee('googletagmanager.com/ns.html?id=GTM-TEST123', false);
        $response->assertDontSee("googletagmanager.com/gtag/js?id=G-TEST12345", false);
        $response->assertSee('window.gtmTrackingEnabled = true', false);
    }

    public function test_direct_ga_snippet_still_loads_when_no_gtm_is_configured(): void
    {
        $settings = $this->createSettings([
            'google_tag_manager_id' => null,
            'google_analytics_id' => 'G-ONLYGA123',
            'gtm_custom_head_script' => null,
            'gtm_custom_body_noscript' => null,
        ]);

        $response = $this->renderTrackingPage($settings);

        $response->assertOk();
        $response->assertSee('googletagmanager.com/gtag/js?id=G-ONLYGA123', false);
        $response->assertSee("gtag('config', 'G-ONLYGA123')", false);
        $response->assertDontSee("https://www.googletagmanager.com/gtm.js?id='+i+dl", false);
        $response->assertSee('window.gtmTrackingEnabled = false', false);
    }

    public function test_bangjeff_inertia_root_uses_setting_backed_tracking_bootstrap(): void
    {
        $settings = $this->createSettings([
            'public_theme' => 'bangjeff',
            'google_tag_manager_id' => 'GTM-INERTIA123',
            'google_analytics_id' => 'G-INERTIA123',
            'facebook_pixel_id' => '1234567890',
        ]);

        $response = $this->renderTrackingPage($settings);

        $response->assertOk();
        $response->assertSee("https://www.googletagmanager.com/gtm.js?id='+i+dl", false);
        $response->assertSee('GTM-INERTIA123', false);
        $response->assertDontSee('googletagmanager.com/gtag/js?id=G-INERTIA123', false);
        $response->assertSee('connect.facebook.net/en_US/fbevents.js', false);
        $response->assertSee("fbq('init', '1234567890')", false);
        $response->assertSee('window.gtmTrackingEnabled = true', false);
        $response->assertSee('window.pushDataLayerEvent = function', false);
        $response->assertSee('googletagmanager.com/ns.html?id=GTM-INERTIA123', false);
    }

    public function test_tiktok_pixel_loads_pageview_once_when_configured(): void
    {
        config([
            'services.tiktok.pixel_id' => 'C123456789012345',
            'services.tiktok.access_token' => 'tiktok-test-token',
        ]);
        $settings = $this->createSettings();

        $response = $this->renderTrackingPage($settings);

        $response->assertOk();
        $response->assertSee("ttq.load('C123456789012345')", false);
        $this->assertSame(1, substr_count($response->getContent(), "ttq.load('C123456789012345')"));
        $this->assertSame(1, substr_count($response->getContent(), 'ttq.page()'));
        $response->assertDontSee("ttq.track('CompletePayment'", false);
    }

    public function test_tiktok_pixel_uses_database_pixel_and_hides_access_token(): void
    {
        config([
            'services.tiktok.pixel_id' => 'CENV123456789012',
            'services.tiktok.access_token' => 'environment-secret-token',
        ]);
        $settings = $this->createSettings([
            'tiktok_tracking_enabled' => true,
            'tiktok_pixel_id' => 'CDB1234567890123',
            'tiktok_access_token' => 'database-secret-token',
        ]);

        $response = $this->renderTrackingPage($settings);

        $response->assertOk();
        $response->assertSee("ttq.load('CDB1234567890123')", false);
        $response->assertDontSee('CENV123456789012', false);
        $response->assertDontSee('database-secret-token', false);
        $response->assertDontSee('environment-secret-token', false);
        $response->assertDontSee((string) $settings->getRawOriginal('tiktok_access_token_encrypted'), false);
    }

    public function test_tenant_storefront_does_not_load_main_tiktok_pixel(): void
    {
        config([
            'services.tiktok.pixel_id' => 'C123456789012345',
            'services.tiktok.access_token' => 'tiktok-test-token',
        ]);
        $tenant = new Tenant([
            'name' => 'Tenant Pixel',
            'subdomain' => 'tenant-pixel',
            'status' => 'active',
        ]);
        $tenant->setAttribute('id', 999);
        app(TenantContext::class)->set($tenant);

        try {
            $html = view('partials.tracking-bootstrap', [
                'trackingSettings' => $this->createSettings(),
            ])->render();
        } finally {
            app(TenantContext::class)->clear();
        }

        $this->assertStringNotContainsString("ttq.load('C123456789012345')", $html);
    }

    public function test_bangjeff_inertia_root_keeps_custom_gtm_server_rendered(): void
    {
        $settings = $this->createSettings([
            'public_theme' => 'bangjeff',
            'google_tag_manager_id' => 'GTM-INERTIA123',
            'google_analytics_id' => 'G-INERTIA123',
            'gtm_custom_head_script' => '<script src="https://gtm.istanatopup.com/inertia-loader.js"></script>',
            'gtm_custom_body_noscript' => '<noscript><iframe src="https://gtm.istanatopup.com/inertia-ns.html?id=GTM-CUSTOM"></iframe></noscript>',
        ]);

        $response = $this->renderTrackingPage($settings);

        $response->assertOk();
        $response->assertSee('https://gtm.istanatopup.com/inertia-loader.js', false);
        $response->assertSee('https://gtm.istanatopup.com/inertia-ns.html?id=GTM-CUSTOM', false);
        $response->assertDontSee("https://www.googletagmanager.com/gtm.js?id='+i+dl", false);
        $response->assertDontSee('googletagmanager.com/gtag/js?id=G-INERTIA123', false);
        $response->assertDontSee('googletagmanager.com/ns.html?id=GTM-INERTIA123', false);
    }

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
            'url_wa' => 'wa.me/test',
            'url_ig' => 'instagram.com/test',
            'url_tiktok' => 'tiktok.com/test',
            'url_youtube' => 'youtube.com/test',
            'url_fb' => 'facebook.com/test',
            'topupindo_api' => 'test_api',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'paydisini_apikey' => 'test_paydisini',
            'tripay_api' => 'test_api_key',
            'tripay_merchant_code' => 'test_merchant',
            'tripay_private_key' => 'test_private',
            'username_digi' => 'test_digi',
            'api_key_digi' => 'test_digi_key',
            'apigames_secret' => 'secret-123',
            'apigames_merchant' => 'merchant-123',
            'vip_apiid' => 'test_vip_id',
            'vip_apikey' => 'test_vip_key',
            'apikey_bangjeff' => 'test_bangjeff_key',
            'order_prefik' => 'INV',
            'gtm_custom_head_script' => null,
            'gtm_custom_body_noscript' => null,
        ], $overrides));
    }

    private function renderTrackingPage(SettingWeb $settings)
    {
        view()->share('config', (object) array_merge([
            'logo_header' => 'assets/logo-header.png',
            'logo_footer' => 'assets/logo-footer.png',
            'logo_favicon' => 'assets/favicon.ico',
        ], $settings->getAttributes()));

        Pembelian::create([
            'order_id' => 'INV-TRACKING-001',
            'username' => 'tracking-user',
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'Tracking User',
            'layanan' => 'Membership Mingguan',
            'harga' => 15000,
            'profit' => 1000,
            'provider_order_id' => '',
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::create([
            'order_id' => 'INV-TRACKING-001',
            'harga' => 15000,
            'no_pembayaran' => 'PAY123',
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-TRACKING-001',
        ]);

        return $this->get('/id/invoices/INV-TRACKING-001');
    }
}
