<?php

namespace Tests\Feature\Bot;

use App\Http\Controllers\TriPayController;
use App\Models\Method;
use App\Models\User;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\TelegramChannelMembershipService;
use App\Services\Deposit\DepositService;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PaymentMethodCatalogService;
use App\Services\Whatsapp\WhatsappUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WhatsappDepositBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('setting_webs')->insert([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test,web',
            'url_wa' => 'https://wa.me/123',
            'url_ig' => 'https://ig.com/test',
            'url_tiktok' => 'https://tiktok.com/test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://fb.com/test',
            'topupindo_api' => 'fake_api',
            'warna1' => '#fff',
            'warna2' => '#fff',
            'warna3' => '#fff',
            'warna4' => '#fff',
            'paydisini_apikey' => 'fake',
            'order_prefik' => 'TRX',
            'deposit_jalur' => 'tripay',
            'tripay_api' => 'fake_api_key',
            'tripay_merchant_code' => 'T1234',
            'tripay_private_key' => 'fake_private',
        ]);

        Method::create([
            'name' => 'BCA',
            'code' => 'BCA',
            'tipe' => 'bank',
            'images' => 'bca.png',
            'keterangan' => 'Bank BCA',
            'payment' => 'Manual',
            'min_pembelian' => 10000,
            'max_pembelian' => 1000000,
            'statuspayment' => 1,
        ]);
    }

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
            null,
            app(DepositService::class),
        );
    }

    public function test_unregistered_sender_is_denied_before_gateway_work(): void
    {
        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:message-1',
            'message_id' => 'whatsapp:message-1',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertStringContainsString('belum terdaftar', strtolower($response['text']));
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_registered_unverified_sender_is_denied(): void
    {
        User::factory()->create(['no_wa' => '6281234567890']);

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:message-2',
            'message_id' => 'whatsapp:message-2',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertStringContainsString('belum terverifikasi', strtolower($response['text']));
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_verified_sender_creates_deposit_for_resolved_user(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldReceive('request')->once()->andReturn([
                'success' => true,
                'amount' => 15000,
                'payment_code' => '1234567890',
                'pay_url' => 'https://tripay.co.id/checkout/T123456789',
                'reference' => 'T123456789',
                'expired_at' => time() + 3600,
            ]);
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:message-3',
            'message_id' => 'whatsapp:message-3',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertStringContainsString('Kode Bayar / VA', $response['text']);
        $this->assertDatabaseHas('deposits', [
            'username' => User::where('no_wa', '6281234567890')->value('username'),
            'source' => 'whatsapp_gateway',
            'external_message_id' => 'whatsapp:message-3',
        ]);
        $this->assertArrayNotHasKey('photo_url', $response);
    }

    public function test_deposit_requires_message_id(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertStringContainsString('ID yang valid', $response['text']);
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_qr_link_is_sent_as_media_without_invoice_url(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldReceive('request')->once()->andReturn([
                'success' => true,
                'amount' => 15000,
                'payment_code' => 'QRIS-PAYLOAD',
                'qr_url' => 'https://cdn.example.test/qr/transaction.png',
                'pay_url' => 'https://tripay.co.id/checkout/should-not-be-sent',
                'reference' => 'T-QR-1',
                'expired_at' => time() + 3600,
            ]);
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:qr-link-1',
            'message_id' => 'whatsapp:qr-link-1',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertArrayHasKey('photo_url', $response);
        $this->assertSame('https://cdn.example.test/qr/transaction.png', $response['photo_url']);
        $this->assertStringNotContainsString('tripay.co.id/checkout', $response['text']);
    }

    public function test_raw_qr_payload_is_converted_to_media(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldReceive('request')->once()->andReturn([
                'success' => true,
                'amount' => 15000,
                'payment_code' => null,
                'qr_payload' => '000201010212TESTPAYLOAD',
                'reference' => 'T-QR-2',
                'expired_at' => time() + 3600,
            ]);
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:qr-payload-1',
            'message_id' => 'whatsapp:qr-payload-1',
            'whatsapp' => '6281234567890',
        ]);

        $this->assertStringStartsWith(
            'https://api.qrserver.com/v1/create-qr-code/?size=512x512&margin=15&data=',
            $response['photo_url'],
        );
        $this->assertStringNotContainsString('api.qrserver.com', $response['text']);
    }

    public function test_duplicate_webhook_message_replays_the_same_deposit(): void
    {
        User::factory()->create([
            'no_wa' => '6281234567890',
            'whatsapp_verified_at' => now(),
        ]);

        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldReceive('request')->once()->andReturn([
                'success' => true,
                'amount' => 15000,
                'payment_code' => '1234567890',
                'reference' => 'T-DUP-1',
                'expired_at' => time() + 3600,
            ]);
        });

        $context = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:duplicate-1',
            'message_id' => 'whatsapp:duplicate-1',
            'whatsapp' => '6281234567890',
        ];
        $first = $this->handler()->handle('deposit', ['15000', 'BCA'], $context);
        $second = $this->handler()->handle('deposit', ['15000', 'BCA'], $context);

        $this->assertSame($first['text'], $second['text']);
        $this->assertDatabaseCount('deposits', 1);
        $this->assertDatabaseCount('pembayarans', 1);
    }
}
