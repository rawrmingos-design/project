<?php

namespace Tests\Feature;

use App\Models\SettingWeb;
use App\Models\WhatsappTemplate;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_inactive_transaction_template_does_not_call_provider(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
        ]);

        WhatsappTemplate::query()->create([
            'slug' => 'transaction_pending',
            'name' => 'Transaksi Pending',
            'details' => null,
            'content' => 'Pending',
            'is_active' => false,
        ]);

        Http::fake();

        $result = app(WhatsappNotificationService::class)->sendNotification(
            '085792464508',
            'transaction_pending',
            ['order_id' => 'INV-INACTIVE-WA-001'],
        );

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_missing_transaction_template_does_not_call_provider(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
        ]);

        Http::fake();

        $result = app(WhatsappNotificationService::class)->sendNotification(
            '085792464508',
            'transaction_pending',
            ['order_id' => 'INV-MISSING-WA-001'],
        );

        $this->assertFalse($result['success']);
        Http::assertNothingSent();
    }

    public function test_transaction_success_notification_renders_verified_payment_message(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
        ]);

        WhatsappTemplate::query()->create([
            'slug' => 'transaction_success',
            'name' => 'Transaksi Sukses',
            'details' => '_Variables: {order_id}, {product}_',
            'content' => "✅ *PEMBAYARAN BERHASIL DIVERIFIKASI!*\n\nTerima kasih telah berbelanja di Z-Vault Store.\n\n🧾 *RINCIAN TRANSAKSI*\n├ Nomor Invoice: *{order_id}*\n└ Produk: *{product}*\n\n🔐 Jika ada kendala hubungi admin utama:\nchat admin @mings dan kirimkan id pesanan nya",
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        app(WhatsappNotificationService::class)->sendNotification('085792464508', 'transaction_success', [
            'order_id' => 'INV-TRIPAY-001',
            'product' => 'Mobile Legends 86 Diamonds',
        ]);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.fonnte.com/send'
                && $request->hasHeader('Authorization', 'fonnte-token')
                && ($request->data()['target'] ?? null) === '085792464508'
                && ($request->data()['message'] ?? null) === "✅ *PEMBAYARAN BERHASIL DIVERIFIKASI!*\n\nTerima kasih telah berbelanja di Z-Vault Store.\n\n🧾 *RINCIAN TRANSAKSI*\n├ Nomor Invoice: *INV-TRIPAY-001*\n└ Produk: *Mobile Legends 86 Diamonds*\n\n🔐 Jika ada kendala hubungi admin utama:\nchat admin @mings dan kirimkan id pesanan nya";
        });
    }

    public function test_transaction_pending_notification_content_remains_unchanged(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
        ]);

        $pendingContent = "*Konfirmasi Pesanan*\n\nQRIS dan VA tetap mengikuti instruksi pembayaran yang ada.";

        WhatsappTemplate::query()->create([
            'slug' => 'transaction_pending',
            'name' => 'Transaksi Pending',
            'details' => null,
            'content' => $pendingContent,
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true], 200),
        ]);

        app(WhatsappNotificationService::class)->sendNotification('085792464508', 'transaction_pending');

        Http::assertSent(function ($request) use ($pendingContent): bool {
            return $request->url() === 'https://api.fonnte.com/send'
                && ($request->data()['message'] ?? null) === $pendingContent;
        });
    }

    public function test_easywa_returns_success_when_status_is_ready(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Http::fake([
            'https://api.easywa.id/v1/status' => Http::response([
                'status' => 'ready',
                'number' => '628123456789',
                'msg' => 'Device connected',
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertTrue($result['success']);
        $this->assertSame('ready', $result['status']);
        $this->assertSame('Device connected', $result['message']);
    }

    public function test_easywa_returns_success_when_status_is_qr(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Http::fake([
            'https://api.easywa.id/v1/status' => Http::response([
                'status' => 'qr',
                'qr' => 'data:image/png;base64,iVBORw0...',
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertTrue($result['success']);
        $this->assertSame('qr', $result['status']);
    }

    public function test_easywa_returns_success_when_status_is_starting(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Http::fake([
            'https://api.easywa.id/v1/status' => Http::response([
                'status' => 'starting',
                'msg' => 'Initializing...',
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertTrue($result['success']);
        $this->assertSame('starting', $result['status']);
    }

    public function test_easywa_returns_failed_when_status_is_unknown(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Http::fake([
            'https://api.easywa.id/v1/status' => Http::response([
                'status' => 'disconnected',
                'msg' => 'Device offline',
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertSame('disconnected', $result['status']);
    }

    public function test_easywa_returns_error_when_email_is_blank(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => '',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Konfigurasi EasyWA belum lengkap', $result['message']);
    }

    public function test_easywa_returns_error_when_secret_key_is_blank(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => '',
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Konfigurasi EasyWA belum lengkap', $result['message']);
    }

    public function test_easywa_handles_http_500_error_gracefully(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Http::fake([
            'https://api.easywa.id/v1/status' => Http::response('Internal Server Error', 500),
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('EasyWA HTTP 500', $result['message']);
    }

    public function test_easywa_handles_connection_timeout_gracefully(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        // Simulate connection timeout (ConnectionException)
        Http::fake([
            'https://api.easywa.id/v1/status' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out after 10000 milliseconds');
            },
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Tidak dapat terhubung ke EasyWA API', $result['message']);
    }

    public function test_easywa_handles_generic_exception_gracefully(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        // Simulate unexpected exception
        Http::fake([
            'https://api.easywa.id/v1/status' => function () {
                throw new \RuntimeException('Unexpected error occurred');
            },
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Error saat cek status EasyWA', $result['message']);
        $this->assertStringContainsString('Unexpected error occurred', $result['message']);
    }

    public function test_easywa_async_send_uses_fixed_one_second_delay(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
            'easywa_send_type' => 'async',
            'easywa_send_delay' => 999,
        ]);

        Http::fake([
            'https://api.easywa.id/v1/send-message' => Http::response([
                'status' => true,
                'msg' => 'queued',
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)
            ->sendTestMessage('085792464508', 'Halo test');

        $this->assertTrue($result['success']);
        $this->assertSame('queued', $result['message']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.easywa.id/v1/send-message'
                && ($request->data()['type'] ?? null) === 'async'
                && ($request->data()['delay'] ?? null) === 1;
        });
    }

    public function test_get_provider_status_returns_unsupported_message_for_non_easywa_provider(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Cek status otomatis hanya tersedia untuk EasyWA', $result['message']);
    }

    public function test_easywa_circuit_breaker_opens_after_threshold_failures(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Cache::forget('whatsapp:easywa:failures');
        Cache::forget('whatsapp:easywa:open_until');

        Http::fake([
            'https://api.easywa.id/v1/send-message' => Http::response([
                'status' => false,
                'msg' => 'Gagal',
            ], 200),
        ]);

        $service = app(WhatsappNotificationService::class);

        // 3 failures = threshold → circuit opens
        $service->sendTestMessage('0811', 'test');
        $service->sendTestMessage('0811', 'test');
        $service->sendTestMessage('0811', 'test');

        $this->assertNotNull(Cache::get('whatsapp:easywa:open_until'));
    }

    public function test_easywa_circuit_breaker_blocks_subsequent_calls_when_open(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        // Manually open the circuit
        Cache::put('whatsapp:easywa:open_until', now()->addMinutes(2)->timestamp, 300);
        Cache::put('whatsapp:easywa:failures', 5, 300);

        Http::fake([
            'https://api.easywa.id/v1/send-message' => Http::response(['status' => true], 200),
        ]);

        $result = app(WhatsappNotificationService::class)->sendTestMessage('0811', 'test');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('sedang bermasalah', $result['message']);
        Http::assertNothingSent();
    }

    public function test_easywa_circuit_breaker_resets_after_cooldown(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        // Set open_until to the past → circuit should be closed
        Cache::put('whatsapp:easywa:open_until', now()->subMinute()->timestamp, 300);
        Cache::put('whatsapp:easywa:failures', 5, 300);

        Http::fake([
            'https://api.easywa.id/v1/send-message' => Http::response([
                'status' => true,
                'msg' => 'sent',
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)->sendTestMessage('0811', 'test');

        $this->assertTrue($result['success']);
        Http::assertSentCount(1);
        // circuit keys should be gone after successful send
        $this->assertNull(Cache::get('whatsapp:easywa:failures'));
        $this->assertNull(Cache::get('whatsapp:easywa:open_until'));
    }

    public function test_easywa_send_uses_8_second_timeout(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Cache::forget('whatsapp:easywa:failures');
        Cache::forget('whatsapp:easywa:open_until');

        Http::fake([
            'https://api.easywa.id/v1/send-message' => Http::response([
                'status' => true,
                'msg' => 'sent',
            ], 200),
        ]);

        app(WhatsappNotificationService::class)->sendTestMessage('0811', 'test');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.easywa.id/v1/send-message';
        });
    }

    public function test_easywa_status_circuit_open_blocks_status_check(): void
    {
        $this->createSettings([
            'wa_provider' => 'easywa',
            'easywa_email' => 'test@example.com',
            'easywa_secret_key' => 'test-secret-key',
        ]);

        Cache::put('whatsapp:easywa:open_until', now()->addMinutes(2)->timestamp, 300);

        Http::fake([
            'https://api.easywa.id/v1/status' => Http::response(['status' => 'ready'], 200),
        ]);

        $result = app(WhatsappNotificationService::class)->getProviderStatus();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('sedang bermasalah', $result['message']);
        Http::assertNothingSent();
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

    public function test_openwa_provider_sends_to_openwa_api(): void
    {
        $this->createSettings([
            'wa_provider' => 'openwa',
            'wa_key' => 'openwa-token',
            'wa_number' => '6287780901780',
            'openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
        ]);

        // AppServiceProvider boot() runs before this test creates SettingWeb,
        // so bot.openwa_session_id must be set explicitly for the URL to match.
        config(['bot.openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5']);

        Http::fake([
            'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-text' => Http::response([
                'messageId' => '3EB060FDB39ACF6367C42E',
                'timestamp' => 1787070472,
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)
            ->sendTestMessage('085792464508', 'Halo dari OpenWA');

        $this->assertTrue($result['success']);
        $this->assertSame('openwa', $result['provider']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-text'
                && $request->hasHeader('Authorization', 'Bearer openwa-token')
                && ($request->data()['chatId'] ?? null) === '6285792464508@s.whatsapp.net'
                && ($request->data()['text'] ?? null) === 'Halo dari OpenWA';
        });
    }

    public function test_openwa_provider_custom_token_uses_bot_key(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
            'openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
        ]);

        config(['bot.openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5']);

        Http::fake([
            'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-text' => Http::response([
                'success' => true,
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)
            ->sendMessage('085792464508', 'Halo bot', null, 'custom-openwa-key');

        $this->assertTrue($result['success']);
        $this->assertSame('openwa', $result['provider']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-text'
                && $request->hasHeader('Authorization', 'Bearer custom-openwa-key');
        });
    }

    public function test_openwa_provider_sends_image_when_url_provided(): void
    {
        $this->createSettings([
            'wa_provider' => 'openwa',
            'wa_key' => 'openwa-token',
            'wa_number' => '6287780901780',
            'openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
        ]);

        config(['bot.openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5']);

        Http::fake([
            'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-image' => Http::response([
                'messageId' => 'IMG-001',
                'timestamp' => 1787070472,
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)
            ->sendMessage('085792464508', 'Silakan scan QRIS', 'https://cdn.example.test/qr/transaksi.png');

        $this->assertTrue($result['success']);
        $this->assertSame('openwa', $result['provider']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-image'
                && $request->hasHeader('Authorization', 'Bearer openwa-token')
                && ($request->data()['chatId'] ?? null) === '6285792464508@s.whatsapp.net'
                && ($request->data()['url'] ?? null) === 'https://cdn.example.test/qr/transaksi.png'
                && ($request->data()['caption'] ?? null) === 'Silakan scan QRIS'
                && ! isset($request->data()['text']);
        });
    }

    public function test_openwa_custom_token_sends_image_when_url_provided(): void
    {
        $this->createSettings([
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
            'openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
        ]);

        config(['bot.openwa_session_id' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5']);

        Http::fake([
            'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-image' => Http::response([
                'messageId' => 'IMG-002',
                'timestamp' => 1787070472,
            ], 200),
        ]);

        $result = app(WhatsappNotificationService::class)
            ->sendMessage('085792464508', 'Scan QRIS ini', 'https://cdn.example.test/qr/bot-order.png', 'custom-openwa-key');

        $this->assertTrue($result['success']);
        $this->assertSame('openwa', $result['provider']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://wagateway.jasakoding.web.id/api/sessions/f802a400-0cf5-4c28-b7b0-aa30c169aee5/messages/send-image'
                && $request->hasHeader('Authorization', 'Bearer custom-openwa-key')
                && ($request->data()['url'] ?? null) === 'https://cdn.example.test/qr/bot-order.png'
                && ($request->data()['caption'] ?? null) === 'Scan QRIS ini';
        });
    }
}
