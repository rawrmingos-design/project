<?php

namespace Tests\Feature\Bot;

use App\Models\User;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\TelegramChannelMembershipService;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PaymentMethodCatalogService;
use App\Services\Whatsapp\WhatsappLinkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappLinkingBotTest extends TestCase
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
        );
    }

    public function test_link_command_verifies_only_the_sender_number(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['no_wa' => null]);
        $challenge = app(WhatsappLinkService::class)->createChallenge($user, '081234567890');

        $response = $this->handler()->handle('link', [$challenge['code']], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertStringContainsString('berhasil ditautkan', strtolower($response['text']));
        $this->assertSame('6281234567890', $user->fresh()->no_wa);
        $this->assertNotNull($user->fresh()->whatsapp_verified_at);
    }

    public function test_link_command_rejects_missing_sender_and_invalid_code_without_querying_account(): void
    {
        $response = $this->handler()->handle('link', ['123456'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:unknown',
            'whatsapp' => null,
        ]);

        $this->assertStringContainsString('Format salah', $response['text']);

        $response = $this->handler()->handle('link', ['not-an-email'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:unknown',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertStringContainsString('Format salah', $response['text']);
    }

    public function test_status_command_distinguishes_linked_unverified_and_unregistered(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $context = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'whatsapp' => '6281234567890',
        ];
        $response = $this->handler()->handle('account_status', [], $context);
        $this->assertStringContainsString('sudah terverifikasi', $response['text']);

        $unverified = User::factory()->create(['no_wa' => '6281234567891']);
        $response = $this->handler()->handle('account_status', [], [
            ...$context,
            'external_user_id' => 'whatsapp:6281234567891',
            'whatsapp' => '6281234567891',
        ]);
        $this->assertStringContainsString('belum terverifikasi', $response['text']);
        $this->assertSame('6281234567891', $unverified->no_wa);

        $response = $this->handler()->handle('account_status', [], [
            ...$context,
            'external_user_id' => 'whatsapp:6281234567892',
            'whatsapp' => '6281234567892',
        ]);
        $this->assertStringContainsString('belum terdaftar', $response['text']);
    }
}
