<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_uses_site_settings_and_installable_fields(): void
    {
        config(['app.url' => 'https://egymarket.id']);

        SettingWeb::query()->create([
            'judul_web' => 'Egy Market Topup Store',
            'deskripsi_web' => 'Top up game cepat dan aman.',
            'keywords' => 'topup,game',
            'logo_favicon' => 'assets/logo/favicon.webp',
            'warna1' => '#123456',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#654321',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
        ]);

        $response = $this->get('/site.webmanifest');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/manifest+json; charset=UTF-8');
        $response->assertJsonPath('id', '/id?source=pwa');
        $response->assertJsonPath('name', 'Egy Market Topup Store');
        $response->assertJsonPath('short_name', 'Egy Market T');
        $response->assertJsonPath('description', 'Top up game cepat dan aman.');
        $response->assertJsonPath('lang', 'id-ID');
        $response->assertJsonPath('dir', 'ltr');
        $response->assertJsonPath('start_url', '/id?source=pwa');
        $response->assertJsonPath('scope', '/');
        $response->assertJsonPath('display', 'standalone');
        $response->assertJsonPath('theme_color', '#123456');
        $response->assertJsonPath('background_color', '#654321');
        $response->assertJsonPath('categories.0', 'shopping');
        $response->assertJsonPath('categories.1', 'games');
        $response->assertJsonPath('categories.2', 'entertainment');
        $response->assertJsonPath('icons.0.src', 'https://egymarket.id/assets/pwa/icon-192.png');
        $response->assertJsonPath('icons.0.sizes', '192x192');
        $response->assertJsonPath('icons.0.type', 'image/png');
        $response->assertJsonPath('icons.1.src', 'https://egymarket.id/assets/pwa/icon-512.png');
        $response->assertJsonPath('icons.1.sizes', '512x512');
        $response->assertJsonPath('icons.2.src', 'https://egymarket.id/assets/pwa/icon-maskable-512.png');
        $response->assertJsonPath('icons.2.purpose', 'maskable');
        $response->assertJsonPath('shortcuts.0.name', 'Cari Transaksi');
        $response->assertJsonPath('shortcuts.0.url', '/id/invoices');
        $response->assertJsonPath('shortcuts.1.name', 'Daftar Harga');
        $response->assertJsonPath('shortcuts.1.url', '/id/price-list');
        $response->assertJsonPath('shortcuts.2.name', 'Leaderboard');
        $response->assertJsonPath('shortcuts.2.url', '/id/leaderboard');
    }

    public function test_homepage_layout_links_manifest_and_registers_service_worker(): void
    {
        $response = $this->get('/id');

        $response->assertOk();
        $response->assertSee('/site.webmanifest', false);
        $response->assertSee("navigator.serviceWorker.register('/sw.js')", false);
        $response->assertSee('apple-mobile-web-app-capable', false);
        $response->assertSee('pwa-install-card', false);
        $response->assertSee('beforeinstallprompt', false);
        $response->assertSee('pwa_install_prompt_shown', false);
        $response->assertSee('Install manual:', false);
        $response->assertSee('pwa_install_manual_hint_shown', false);
        $response->assertSee('data-pwa-update-notice', false);
        $response->assertSee('Update tersedia', false);
        $response->assertSee('pwa-update-refresh', false);
        $response->assertSee('updatefound', false);
        $response->assertSee('controllerchange', false);
        $response->assertSee('format-detection', false);
        $response->assertSee('telephone=no', false);
        $response->assertSee('name="theme-color"', false);
        $response->assertSee('data-pwa-connection-toast', false);
        $response->assertSee("window.addEventListener('offline'", false);
        $response->assertSee("window.addEventListener('online'", false);
        $response->assertSee('Koneksi kembali normal', false);
        $response->assertSee('pwa_connectivity_probe', false);
        $response->assertSee('Koneksi kembali, reload ditahan', false);
        $response->assertSee('Refresh sekarang', false);
        $response->assertSee('Refresh nanti', false);
        $response->assertSee('hasFocusedOrDirtyFormState', false);
    }

    public function test_service_worker_contains_critical_network_only_prefixes(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertIsString($sw);
        $this->assertStringContainsString("const CACHE_VERSION = 'v1';", $sw);
        $this->assertStringContainsString("'/admin'", $sw);
        $this->assertStringContainsString("'/filament'", $sw);
        $this->assertStringContainsString("'/api'", $sw);
        $this->assertStringContainsString("'/callback'", $sw);
        $this->assertStringContainsString("'/id/invoices'", $sw);
        $this->assertStringContainsString("'/id/deposit'", $sw);
        $this->assertStringContainsString("'/id/dashboard'", $sw);
        $this->assertStringContainsString("'/id/reseller'", $sw);
        $this->assertStringContainsString("event.data && event.data.type === 'SKIP_WAITING'", $sw);
        $this->assertStringNotContainsString('.then(() => self.skipWaiting())', $sw);
        $this->assertStringContainsString('PRECACHE_URLS', $sw);
        $this->assertStringContainsString("'/assets/css/pjojikhhoyutyrtd.css'", $sw);
        $this->assertStringContainsString("'/assets/js/oo324ddod2323sd2dd.js'", $sw);
        $this->assertStringContainsString('STATIC_CACHE_MAX_ENTRIES', $sw);
        $this->assertStringContainsString('trimCache', $sw);
        $this->assertStringContainsString('getNormalizedStaticCacheRequest', $sw);
        $this->assertStringContainsString('`${url.origin}${url.pathname}`', $sw);
        $this->assertStringContainsString("self.addEventListener('push'", $sw);
        $this->assertStringContainsString("self.addEventListener('notificationclick'", $sw);
        $this->assertStringContainsString("'/id/reseller/settings'", $sw);
        $this->assertStringContainsString('safelyParseJson', $sw);
        $this->assertStringContainsString("'PING_CONNECTION_STATUS'", $sw);
        $this->assertStringContainsString("'CONNECTION_STATUS'", $sw);
    }

    public function test_install_prompt_partial_blocks_sensitive_route_patterns(): void
    {
        $partial = file_get_contents(resource_path('views/template/id/partials/pwa-install-prompt.blade.php'));

        $this->assertIsString($partial);
        $this->assertStringContainsString("request()->is('id')", $partial);
        $this->assertStringContainsString("'admin*'", $partial);
        $this->assertStringContainsString("'filament*'", $partial);
        $this->assertStringContainsString("'id/invoices*'", $partial);
        $this->assertStringContainsString("'id/deposit*'", $partial);
        $this->assertStringContainsString("'id/dashboard*'", $partial);
        $this->assertStringContainsString("'id/settings*'", $partial);
        $this->assertStringContainsString("'id/reseller*'", $partial);
        $this->assertStringContainsString('data-pwa-install-hint', $partial);
        $this->assertStringContainsString('pwa_install_manual_hint_shown', $partial);
        $this->assertStringContainsString('Add to Home Screen', $partial);
    }

    public function test_pwa_static_assets_exist_in_public_directory(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString('storefront-pwa', file_get_contents(public_path('sw.js')));
        $this->assertStringContainsString('SKIP_WAITING', file_get_contents(public_path('sw.js')));

        $this->assertFileExists(public_path('offline.html'));
        $offline = file_get_contents(public_path('offline.html'));
        $this->assertStringContainsString('Koneksi internet lagi turun', $offline);
        $this->assertStringContainsString('Coba Lagi Sekarang', $offline);
        $this->assertStringContainsString('data-offline-guidance', $offline);
        $this->assertStringContainsString('Storefront Offline Mode', $offline);
        $this->assertStringContainsString('Yang masih bisa', $offline);
        $this->assertStringContainsString('Hindari melanjutkan pembayaran sampai koneksi aktif kembali.', $offline);
        $this->assertStringContainsString('data-offline-brand-badge', $offline);

        $this->assertFileExists(public_path('assets/pwa/icon-192.png'));
        $this->assertFileExists(public_path('assets/pwa/icon-512.png'));
        $this->assertFileExists(public_path('assets/pwa/icon-maskable-512.png'));
        $this->assertFileExists(public_path('assets/pwa/apple-touch-icon.png'));
    }
}

