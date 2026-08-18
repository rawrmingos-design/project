<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\Adapters\OpenWaAdapter;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Mockery;

class OpenWaWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function openwaPayload(array $overrides = []): array
    {
        return array_merge([
            'event' => 'message.received',
            'timestamp' => '2026-08-18T15:00:00.000Z',
            'sessionId' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
            'idempotencyKey' => 'abc-123',
            'deliveryId' => 'del-456',
            'data' => [
                'key' => [
                    'remoteJid' => '6281234567890@s.whatsapp.net',
                    'id' => 'WA-MESSAGE-ID-001',
                ],
                'message' => [
                    'conversation' => 'menu',
                ],
            ],
        ], $overrides);
    }

    public function test_openwa_webhook_payload_maps_to_handler_context(): void
    {
        Cache::flush();

        // Mock outbound — we only assert mapping here, not actual sending
        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function (string $target, string $message) {
                    $this->assertSame('6281234567890', $target);
                    $this->assertNotEmpty($message);

                    return true;
                });
        });

        $adapter = app(OpenWaAdapter::class);
        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($this->openwaPayload()));

        $response = $adapter->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_openwa_webhook_extended_text_message(): void
    {
        Cache::flush();

        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once();
        });

        $adapter = app(OpenWaAdapter::class);
        $payload = $this->openwaPayload([
            'data' => [
                'key' => [
                    'remoteJid' => '6281234567890@s.whatsapp.net',
                    'id' => 'WA-EXT-002',
                ],
                'message' => [
                    'extendedTextMessage' => [
                        'text' => 'deposit 15000',
                    ],
                ],
            ],
        ]);
        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $adapter->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_openwa_webhook_ignores_non_message_events(): void
    {
        $adapter = app(OpenWaAdapter::class);
        $payload = $this->openwaPayload(['event' => 'session.status']);
        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $adapter->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
