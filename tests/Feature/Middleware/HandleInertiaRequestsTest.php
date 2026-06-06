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

        // Test via service directly — avoids needing a full Inertia HTTP round-trip
        // which would require built React assets in the test environment.
        $service = app(PublicSiteConfigService::class);
        $shared = $service->sharedProps();

        $this->assertArrayHasKey('siteConfig', $shared);
        $this->assertArrayHasKey('favicon', $shared['siteConfig']);
        $this->assertEquals('/assets/custom_favicon.png', $shared['siteConfig']['favicon']);
    }
}
