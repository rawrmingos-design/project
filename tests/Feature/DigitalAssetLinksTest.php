<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigitalAssetLinksTest extends TestCase
{
    use RefreshDatabase;

    private const FIXTURE_COLUMNS = [
        'judul_web' => 'Istana Topup',
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
    ];

    private function createSetting(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(
            array_merge(self::FIXTURE_COLUMNS, $overrides),
        );
    }

    public function test_asset_links_statement_is_served_from_settings(): void
    {
        $this->createSetting([
            'android_package_id' => 'com.istanatopup.app',
            'android_cert_fingerprints' => json_encode([
                '2A:B3:C2:30:EC:35:BB:CB:DF:71:25:E7:58:1A:8F:AA:87:B2:78:E7:8B:B1:D9:F1:47:9B:02:CB:BF:2C:9A:4E',
            ]),
        ]);

        $response = $this->get('/.well-known/assetlinks.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');

        $statements = json_decode($response->getContent(), true);
        $this->assertIsArray($statements);
        $this->assertNotEmpty($statements);

        $statement = $statements[0];
        $this->assertSame(
            ['delegate_permission/common.handle_all_urls'],
            $statement['relation'],
        );
        $this->assertSame('android_app', $statement['target']['namespace']);
        $this->assertSame('com.istanatopup.app', $statement['target']['package_name']);
        $this->assertSame(
            ['2A:B3:C2:30:EC:35:BB:CB:DF:71:25:E7:58:1A:8F:AA:87:B2:78:E7:8B:B1:D9:F1:47:9B:02:CB:BF:2C:9A:4E'],
            $statement['target']['sha256_cert_fingerprints'],
        );
    }

    public function test_asset_links_falls_back_to_static_file_when_db_empty(): void
    {
        $this->createSetting();

        $staticPath = public_path('.well-known/assetlinks.json');

        if (! is_file($staticPath)) {
            $this->markTestSkipped('Static assetlinks.json tidak ada di repo.');
        }

        $response = $this->get('/.well-known/assetlinks.json');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/json');
        $this->assertSame(
            json_decode((string) file_get_contents($staticPath), true),
            json_decode($response->getContent(), true),
        );
    }

    public function test_asset_links_serves_per_tenant_values_and_normalizes_fingerprint(): void
    {
        $this->createSetting([
            'android_package_id' => 'com.egymarket.app',
            'android_cert_fingerprints' => json_encode([
                ' aa:bb:cc:dd:ee:ff:00:11:22:33:44:55:66:77:88:99:aa:bb:cc:dd:ee:ff:00:11:22:33:44:55:66:77:88:99 ',
                'bukan-fingerprint-valid',
            ]),
        ]);

        $response = $this->get('/.well-known/assetlinks.json');
        $statement = json_decode($response->getContent(), true)[0];

        $response->assertOk();
        $this->assertSame('com.egymarket.app', $statement['target']['package_name']);
        $this->assertSame(
            ['AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99'],
            $statement['target']['sha256_cert_fingerprints'],
            'Harus uppercase, trim, dan membuang entri tidak valid.',
        );
    }

    public function test_invalid_fingerprint_json_falls_back_to_static_file(): void
    {
        $this->createSetting([
            'android_package_id' => 'com.broken.app',
            'android_cert_fingerprints' => 'not-valid-json{{',
        ]);

        $staticPath = public_path('.well-known/assetlinks.json');

        if (! is_file($staticPath)) {
            $this->markTestSkipped('Static assetlinks.json tidak ada di repo.');
        }

        $response = $this->get('/.well-known/assetlinks.json');

        $response->assertOk();
        $this->assertStringContainsString(
            'com.istanatopup.app',
            $response->getContent(),
            'Harus fallback ke statement statis, bukan serve data rusak.',
        );
    }
}
