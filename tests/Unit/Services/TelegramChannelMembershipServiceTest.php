<?php

namespace Tests\Unit\Services;

use App\Services\Bot\TelegramChannelMembershipService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramChannelMembershipServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
        config([
            'services.telegram-bot-api.token' => 'test-token',
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.id' => '@testchannel',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/testchannel',
            'services.telegram-bot-api.required_channel.cache_seconds' => 120,
        ]);
    }

    public function test_disabled_enforcement_allows_without_request(): void
    {
        config(['services.telegram-bot-api.required_channel.enabled' => false]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
        Http::assertNothingSent();
    }

    public function test_non_telegram_source_allows_without_request(): void
    {
        $result = app(TelegramChannelMembershipService::class)->check([
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:628123',
        ]);

        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
        Http::assertNothingSent();
    }

    public function test_member_statuses_are_allowed(): void
    {
        foreach (['creator', 'administrator', 'member'] as $index => $status) {
            Cache::flush();
            Http::fake([
                '*' => Http::response([
                    'ok' => true,
                    'result' => ['status' => $status],
                ]),
            ]);

            $result = app(TelegramChannelMembershipService::class)->check($this->context(100 + $index));

            $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
        }
    }

    public function test_restricted_member_is_allowed(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'restricted', 'is_member' => true],
            ]),
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
    }

    public function test_non_member_statuses_are_denied(): void
    {
        foreach (['left', 'kicked'] as $index => $status) {
            Cache::flush();
            Http::fake([
                '*' => Http::response([
                    'ok' => true,
                    'result' => ['status' => $status],
                ]),
            ]);

            $result = app(TelegramChannelMembershipService::class)->check($this->context(200 + $index));

            $this->assertSame(TelegramChannelMembershipService::STATUS_NOT_MEMBER, $result['status']);
            $this->assertSame('https://t.me/testchannel', $result['channel_url']);
        }
    }

    public function test_restricted_non_member_is_denied(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'restricted', 'is_member' => false],
            ]),
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_NOT_MEMBER, $result['status']);
    }

    public function test_invalid_configuration_fails_closed(): void
    {
        config(['services.telegram-bot-api.required_channel.url' => 'https://t.me/otherchannel']);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
        Http::assertNothingSent();
    }

    public function test_failed_and_malformed_responses_fail_closed(): void
    {
        foreach ([
            Http::response(['ok' => false], 200),
            Http::response(['message' => 'server error'], 500),
            Http::response('not-json', 200),
        ] as $index => $response) {
            Cache::flush();
            Http::fake(['*' => $response]);

            $result = app(TelegramChannelMembershipService::class)->check($this->context(300 + $index));

            $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
        }
    }

    public function test_connection_failure_fails_closed(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('timeout');
        });

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
    }

    public function test_positive_membership_is_cached(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member'],
            ]),
        ]);

        $service = app(TelegramChannelMembershipService::class);
        $service->check($this->context());
        $result = $service->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
        Http::assertSentCount(1);
    }

    private function context(int $userId = 12345): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:' . $userId,
            'telegram_user_id' => $userId,
        ];
    }
}
