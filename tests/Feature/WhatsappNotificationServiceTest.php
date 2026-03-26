<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsappNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fonnte_successful_queue_response_is_normalized_into_success_message(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'detail' => 'success! message in queue',
                'id' => [148533394],
                'process' => 'pending',
                'requestid' => 425754326,
                'status' => true,
                'target' => ['6285792464508'],
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)
            ->sendTestMessage('085792464508', 'Halo test');

        $this->assertTrue($result['success']);
        $this->assertSame('fonnte', $result['provider']);
        $this->assertSame('pending', $result['process']);
        $this->assertSame(425754326, $result['request_id']);
        $this->assertStringContainsString('success! message in queue', $result['message']);
        $this->assertStringContainsString('process: pending', $result['message']);
    }

    public function test_fonnte_failed_response_uses_reason_in_message(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'invalid-token',
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response([
                'reason' => 'token invalid',
                'status' => false,
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)
            ->sendTestMessage('085792464508', 'Halo test');

        $this->assertFalse($result['success']);
        $this->assertSame('token invalid', $result['message']);
        $this->assertSame('token invalid', $result['reason']);
    }

    private function createSettings(array $overrides = []): void
    {
        SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'topupindo-test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'paydisini-test',
            'order_prefik' => 'INV',
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
        ], $overrides));
    }
}
