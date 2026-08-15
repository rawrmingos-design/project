<?php

namespace Tests\Feature\Bot;

use App\Models\Pembelian;
use App\Models\TelegramIdentity;
use App\Models\User;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\TelegramChannelMembershipService;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PaymentMethodCatalogService;
use App\Services\Whatsapp\WhatsappUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class WhatsappOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function handler(): BotCommandHandler
    {
        return new BotCommandHandler(
            app(GatewayCatalogService::class),
            app(PaymentMethodCatalogService::class),
            app(GatewayPricingService::class),
            app(GatewayCheckIdService::class),
            app(GatewayInvoiceService::class),
            app(BotMessageFormatter::class),
            app(TelegramChannelMembershipService::class),
            app(\App\Services\LeaderboardService::class),
            app(WhatsappUserResolver::class),
        );
    }

    private function context(string $number = '6281234567890'): array
    {
        return [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:' . $number,
            'message_id' => 'whatsapp:history-test',
            'whatsapp' => $number,
        ];
    }

    public function test_linked_sender_receives_only_their_orders_and_numeric_detail_reference_is_scoped(): void
    {
        RateLimiter::clear('bot-history:' . hash_hmac('sha256', '6281234567890', (string) config('app.key')));
        $user = User::factory()->create([
            'username' => 'history-owner',
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);
        $other = User::factory()->create(['username' => 'history-other']);
        $order = Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-ORDER-123',
            'layanan' => 'Game [VIP] Product',
        ]);
        Pembelian::factory()->create([
            'username' => $other->username,
            'order_id' => 'OTHER-ORDER-999',
        ]);

        $list = $this->handler()->handle('history', [], $this->context());
        $detail = $this->handler()->handle('history', ['detail', (string) $order->getKey()], $this->context());
        $foreign = $this->handler()->handle('history', ['detail', (string) Pembelian::query()->where('username', $other->username)->value('id')], $this->context());

        $this->assertStringContainsString('OW••••••••••123', $list['text']);
        $this->assertStringNotContainsString('OTHER-ORDER-999', $list['text']);
        $this->assertStringContainsString('OWNER-ORDER-123', $detail['text']);
        $this->assertStringContainsString('Game \\[VIP\\] Product', $list['text']);
        $this->assertStringContainsString('tidak ditemukan', strtolower($foreign['text']));
    }

    public function test_whatsapp_history_uses_fifteen_numeric_entries_and_preserves_window_from_detail(): void
    {
        Cache::flush();
        RateLimiter::clear('bot-history:' . hash_hmac('sha256', '6281234567890', (string) config('app.key')));
        $user = User::factory()->create([
            'username' => 'history-owner',
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);
        collect(range(1, 16))->each(fn (int $number) => Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-' . $number,
            'created_at' => now()->subMinutes($number),
        ]));
        $handler = $this->handler();

        $first = $handler->handle('history', [], $this->context());
        $this->assertCount(15, array_filter(
            $first['buttons'],
            fn (array $row): bool => ($row[0]['numeric_type'] ?? null) === 'content',
        ));
        $this->assertSame('navigation_next', $first['buttons'][15][0]['numeric_type']);
        $this->assertLessThanOrEqual(64, strlen($first['buttons'][0][0]['callback']));
        $this->assertLessThanOrEqual(64, strlen($first['buttons'][15][0]['callback']));

        $detailParts = preg_split('/\s+/', $first['buttons'][0][0]['callback']) ?: [];
        $detail = $handler->handle(
            array_shift($detailParts),
            $detailParts,
            $this->context(),
        );
        $this->assertSame(
            'history nav ' . $detailParts[2],
            $detail['buttons'][0][0]['callback'],
        );

        Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'INSERTED-LATER',
            'created_at' => now(),
        ]);
        $backParts = preg_split('/\s+/', $detail['buttons'][0][0]['callback']) ?: [];
        $restored = $handler->handle(
            array_shift($backParts),
            $backParts,
            $this->context(),
        );

        $this->assertStringNotContainsString('INSERTED-LATER', $restored['text']);
        $this->assertSame($first['text'], $restored['text']);
    }

    public function test_telegram_history_callbacks_remain_within_sixty_four_bytes(): void
    {
        Cache::flush();
        $user = User::factory()->create(['username' => 'telegram-history-owner']);
        TelegramIdentity::query()->create([
            'user_id' => $user->id,
            'bot_scope' => 'primary',
            'telegram_user_id' => '9876',
            'chat_id' => '12345',
            'linked_at' => now(),
            'verified_at' => now(),
        ]);
        collect(range(1, 6))->each(fn (int $number) => Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'TELEGRAM-' . $number,
            'created_at' => now()->subMinutes($number),
        ]));
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:primary:9876',
            'telegram_user_id' => 9876,
            'telegram_bot_scope' => 'primary',
            'telegram_chat_id' => 12345,
            'telegram_metadata' => [],
        ];

        $response = $this->handler()->handle('history', [], $context);
        $callbacks = collect($response['buttons'])
            ->flatten(1)
            ->pluck('callback');

        $this->assertCount(7, $callbacks);
        $this->assertTrue($callbacks->every(
            fn (string $callback): bool => strlen($callback) <= 64,
        ));
    }

    public function test_unverified_sender_is_denied_and_unlinked_telegram_is_prompted_to_link(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => null,
        ]);

        $unverified = $this->handler()->handle('riwayat', [], $this->context());
        $telegram = $this->handler()->handle('riwayat', [], [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:123',
        ]);

        $this->assertStringContainsString('belum terverifikasi', strtolower($unverified['text']));
        $this->assertStringContainsString('tautkan akun telegram', strtolower($telegram['text']));
    }
}
