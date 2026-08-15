<?php

namespace Tests\Feature\Bot;

use App\Models\Pembelian;
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
