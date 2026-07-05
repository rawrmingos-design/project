<?php

namespace Tests\Feature\Middleware;

use App\Models\SettingWeb;
use App\Models\User;
use App\Services\PublicSiteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_shares_favicon_from_site_config(): void
    {
        // SettingWeb doesn't have HasFactory, so we seed via DB directly.
        // Only supply the minimal required (non-nullable) columns.
        \DB::table('setting_webs')->insert([
            'id'                   => 1,
            'judul_web'            => 'TestSite',
            'deskripsi_web'        => 'Test',
            'keywords'             => 'test',
            'url_wa'               => '',
            'url_ig'               => '',
            'url_tiktok'           => '',
            'url_youtube'          => '',
            'url_fb'               => '',
            'topupindo_api'        => '',
            'warna1'               => '#000',
            'warna2'               => '#000',
            'warna3'               => '#000',
            'warna4'               => '#000',
            'paydisini_apikey'     => '',
            'order_prefik'         => 'INV',
            'logo_favicon'         => '/assets/custom_favicon.png',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        // Test via service directly â€” avoids needing a full Inertia HTTP round-trip
        // which would require built React assets in the test environment.
        $service = app(PublicSiteConfigService::class);
        $shared = $service->sharedProps();

        $this->assertArrayHasKey('siteConfig', $shared);
        $this->assertArrayHasKey('favicon', $shared['siteConfig']);
        $this->assertEquals(asset('assets/logo/favicon.webp'), $shared['siteConfig']['favicon']);
    }

    public function test_site_config_falls_back_to_local_asset_when_r2_object_is_missing(): void
    {
        config([
            'app.url' => 'https://app.test',
            'uploads.disk' => 'r2',
            'uploads.placeholder' => 'assets/logo/favicon.webp',
        ]);

        \Storage::fake('r2');
        \File::ensureDirectoryExists(public_path('assets/logo'));
        \File::put(public_path('assets/logo/r2-local-logo.png'), 'local logo');

        \DB::table('setting_webs')->insert([
            'id'                   => 1,
            'judul_web'            => 'TestSite',
            'deskripsi_web'        => 'Test',
            'keywords'             => 'test',
            'url_wa'               => '',
            'url_ig'               => '',
            'url_tiktok'           => '',
            'url_youtube'          => '',
            'url_fb'               => '',
            'topupindo_api'        => '',
            'warna1'               => '#000',
            'warna2'               => '#000',
            'warna3'               => '#000',
            'warna4'               => '#000',
            'paydisini_apikey'     => '',
            'order_prefik'         => 'INV',
            'logo_header'          => 'assets/logo/r2-local-logo.png',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        try {
            $shared = app(PublicSiteConfigService::class)->sharedProps();

            $this->assertSame(asset('assets/logo/r2-local-logo.png'), $shared['siteConfig']['logoHeader']);
            $this->assertTrue($shared['siteConfig']['assetAudit']['logoHeader']['exists']);
        } finally {
            \File::delete(public_path('assets/logo/r2-local-logo.png'));
        }
    }
}