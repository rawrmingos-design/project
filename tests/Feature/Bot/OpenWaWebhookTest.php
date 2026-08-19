<?php

namespace Tests\Feature\Bot;

use App\Models\InboundSourcePolicy;
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

    protected function setUp(): void
    {
        parent::setUp();

        // Setup OpenWA whitelist policy (same as production seeder)
        $policy = InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'openwa',
            'mode' => 'enforce',
            'is_active' => true,
        ]);
        $policy->entries()->create([
            'value_type' => 'ipv4',
            'value' => '103.31.205.166',
            'is_active' => true,
        ]);
    }

    public function test_openwa_route_rejects_unauthorized_ip(): void
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->postJson('/api/webhooks/bot/openwa', []);

        $response->assertStatus(403);
    }

    public function test_openwa_route_accepts_allowed_ip(): void
    {
        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once();
        });

        $response = $this->withServerVariables(['REMOTE_ADDR' => '103.31.205.166'])
            ->postJson('/api/webhooks/bot/openwa', $this->openwaPayload());

        $response->assertStatus(200);
    }

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

    public function test_openwa_webhook_rejects_invalid_signature(): void
    {
        config(['bot.openwa_webhook_secret' => 'test-secret-123']);

        $adapter = app(OpenWaAdapter::class);
        $payload = $this->openwaPayload();
        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_OPENWA_SIGNATURE' => 'sha256=deadbeef',
        ], json_encode($payload));

        $response = $adapter->handle($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('forbidden', json_decode($response->getContent(), true)['status'] ?? null);
    }

    public function test_openwa_webhook_accepts_valid_signature(): void
    {
        config(['bot.openwa_webhook_secret' => 'test-secret-123']);

        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once();
        });

        $adapter = app(OpenWaAdapter::class);
        $payload = $this->openwaPayload();
        $body = json_encode($payload);
        $signature = 'sha256=' . hash_hmac('sha256', $body, 'test-secret-123');
        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_OPENWA_SIGNATURE' => $signature,
        ], $body);

        $response = $adapter->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_openwa_webhook_flat_payload_shape(): void
    {
        Cache::flush();

        // OpenWA v2+ sends FLAT data: {from, body, type, id} — not nested Baileys shape.
        $this->mock(WhatsappNotificationService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->withArgs(function (string $target, string $message) {
                    $this->assertSame('6285792464508', $target);
                    $this->assertSame('menu', $message);

                    return true;
                });
        });

        $adapter = app(OpenWaAdapter::class);
        $payload = [
            'event' => 'message.received',
            'timestamp' => '2026-08-19T06:05:01.891Z',
            'sessionId' => 'f802a400-0cf5-4c28-b7b0-aa30c169aee5',
            'idempotencyKey' => 'msg_flat_v1',
            'deliveryId' => 'dlv_flat_001',
            'data' => [
                'id' => 'AC659A0084CC1FF1EB4020B4E312B6DF',
                'from' => '6285792464508@c.us',
                'to' => '6287780901780@c.us',
                'chatId' => '6285792464508@c.us',
                'body' => 'menu',
                'type' => 'text',
                'timestamp' => 1787119501,
                'fromMe' => false,
                'isGroup' => false,
                'kind' => 'individual',
                'isStatusBroadcast' => false,
                'contact' => ['pushName' => 'miii'],
            ],
        ];
        $request = Request::create('/api/webhooks/bot/openwa', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $response = $adapter->handle($request);

        $this->assertSame(200, $response->getStatusCode());
    }
}
