<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Services\TikTokSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TikTokSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createSettings(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'TikTok Settings Test',
            'deskripsi_web' => 'TikTok settings test',
            'keywords' => 'tiktok,test',
            'logo_header' => 'logo.png',
            'logo_footer' => 'footer.png',
            'logo_favicon' => 'favicon.ico',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => '-',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => '-',
            'order_prefik' => 'INV',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.tiktok.pixel_id' => 'CENV123456789012',
            'services.tiktok.access_token' => 'env-secret-token',
            'services.tiktok.test_event_code' => 'ENV-TEST-CODE',
        ]);
    }

    public function test_environment_is_used_when_database_values_are_empty(): void
    {
        $this->createSettings();

        $resolver = app(TikTokSettingsService::class);

        $this->assertTrue($resolver->enabled());
        $this->assertSame('CENV123456789012', $resolver->pixelId());
        $this->assertSame('env-secret-token', $resolver->accessToken());
        $this->assertSame('ENV-TEST-CODE', $resolver->testEventCode());
        $this->assertSame('environment', $resolver->pixelIdSource());
        $this->assertSame('environment', $resolver->accessTokenSource());
    }

    public function test_database_values_override_environment_and_token_is_encrypted_hidden(): void
    {
        $settings = $this->createSettings([
            'tiktok_tracking_enabled' => true,
            'tiktok_pixel_id' => 'CDB1234567890123',
            'tiktok_access_token' => 'database-secret-token',
            'tiktok_test_event_code' => 'DB-TEST-CODE',
        ]);

        $rawToken = DB::table('setting_webs')
            ->where('id', 1)
            ->value('tiktok_access_token_encrypted');

        $this->assertNotSame('database-secret-token', $rawToken);
        $this->assertStringNotContainsString('database-secret-token', (string) $rawToken);
        $this->assertArrayNotHasKey('tiktok_access_token_encrypted', $settings->toArray());
        $this->assertStringNotContainsString('database-secret-token', $settings->toJson());

        $resolver = app(TikTokSettingsService::class);
        $this->assertTrue($resolver->enabled());
        $this->assertSame('CDB1234567890123', $resolver->pixelId());
        $this->assertSame('database-secret-token', $resolver->accessToken());
        $this->assertSame('DB-TEST-CODE', $resolver->testEventCode());
        $this->assertSame('database', $resolver->pixelIdSource());
        $this->assertSame('database', $resolver->accessTokenSource());
    }

    public function test_explicit_database_toggle_off_overrides_configured_credentials(): void
    {
        $this->createSettings([
            'tiktok_tracking_enabled' => false,
            'tiktok_pixel_id' => 'CDB1234567890123',
            'tiktok_access_token' => 'database-secret-token',
        ]);

        $this->assertFalse(app(TikTokSettingsService::class)->enabled());
    }

    public function test_blank_virtual_token_does_not_overwrite_existing_ciphertext(): void
    {
        $settings = $this->createSettings([
            'tiktok_access_token' => 'original-secret-token',
        ]);
        $ciphertext = $settings->getRawOriginal('tiktok_access_token_encrypted');

        $settings->fill(['tiktok_access_token' => ''])->save();

        $this->assertSame($ciphertext, $settings->fresh()->getRawOriginal('tiktok_access_token_encrypted'));
        $this->assertSame('original-secret-token', $settings->fresh()->decryptedTiktokAccessToken());
    }
}
