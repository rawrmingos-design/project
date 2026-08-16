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

    public function test_conversational_deposit_preset_nominal(): void
    {
        User::factory()->create(['no_wa' => '6281234567890', 'whatsapp_verified_at' => now()]);
        $ctx = $this->waContext('6281234567890', 'msg-preset');
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $step2 = $handler->handle('1', [], $ctx);

        $this->assertStringContainsString('Pilih Metode Pembayaran', $step2['text']);
        $this->assertStringContainsString('Rp 10.000', $step2['text']);
    }

    public function test_conversational_deposit_custom_nominal_formats(): void
    {
        User::factory()->create(['no_wa' => '6281234567890', 'whatsapp_verified_at' => now()]);
        $ctx = $this->waContext('6281234567890', 'msg-custom');
        $handler = $this->handler();

        $formats = ['15000', '15.000', 'Rp 15.000', 'Rp. 15.000'];

        foreach ($formats as $input) {
            \Illuminate\Support\Facades\Cache::forget($this->checkoutStateKey($ctx));
            $handler->handle('deposit', [], $ctx);
            $step2 = $handler->handle($input, [], $ctx);

            $this->assertStringContainsString('Pilih Metode Pembayaran', $step2['text'], "Failed for format: $input");
            $this->assertStringContainsString('Rp 15.000', $step2['text'], "Failed for format: $input");
        }
    }

    public function test_invalid_nominal_maintains_state(): void
    {
        User::factory()->create(['no_wa' => '6281234567890', 'whatsapp_verified_at' => now()]);
        $ctx = $this->waContext('6281234567890', 'msg-invalid');
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);

        // Invalid inputs
        $invalid1 = $handler->handle('9999', [], $ctx);
        $this->assertStringContainsString('Nominal tidak valid', $invalid1['text']);

        $invalid2 = $handler->handle('abc', [], $ctx);
        $this->assertStringContainsString('Nominal tidak valid', $invalid2['text']);

        // State is maintained, valid input now succeeds
        $step2 = $handler->handle('15000', [], $ctx);
        $this->assertStringContainsString('Pilih Metode Pembayaran', $step2['text']);
    }

    private function checkoutStateKey(array $context): string
    {
        return 'bot:checkout-state:' . hash(
            'sha256',
            implode('|', [
                (string) ($context['source'] ?? ''),
                (string) ($context['external_user_id'] ?? ''),
            ]),
        );
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

        $ctx = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:message-3',
            'message_id' => 'whatsapp:message-3',
            'whatsapp' => '6281234567890',
        ];
        $handler = $this->handler();

        // Step 1: deposit → amount prompt
        $step1 = $handler->handle('deposit', [], $ctx);
        $this->assertStringContainsString('Pilih Jumlah Deposit', $step1['text']);

        // Step 2: custom amount → method prompt
        $step2 = $handler->handle('15000', [], $ctx);
        $this->assertStringContainsString('Pilih Metode Pembayaran', $step2['text']);

        // Step 3: pick method 1 (BCA) → deposit created
        $response = $handler->handle('1', [], $ctx);

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

        // Context deliberately missing message_id / external_message_id.
        $ctx = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'whatsapp' => '6281234567890',
        ];
        $handler = $this->handler();

        // Step 1: deposit → amount prompt (message_id not needed yet)
        $handler->handle('deposit', [], $ctx);

        // Step 2: amount → method prompt
        $handler->handle('15000', [], $ctx);

        // Step 3: pick method → should fail: no message_id
        $response = $handler->handle('1', [], $ctx);

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

        $ctx = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:qr-link-1',
            'message_id' => 'whatsapp:qr-link-1',
            'whatsapp' => '6281234567890',
        ];
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('15000', [], $ctx);
        $response = $handler->handle('1', [], $ctx);

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

        $ctx = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:qr-payload-1',
            'message_id' => 'whatsapp:qr-payload-1',
            'whatsapp' => '6281234567890',
        ];
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('15000', [], $ctx);
        $response = $handler->handle('1', [], $ctx);

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

        $ctx = [
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:duplicate-1',
            'message_id' => 'whatsapp:duplicate-1',
            'whatsapp' => '6281234567890',
        ];
        $handler = $this->handler();

        // Navigate to deposit creation (message_id idempotency key is 'whatsapp:duplicate-1').
        $handler->handle('deposit', [], $ctx);
        $handler->handle('15000', [], $ctx);
        $first = $handler->handle('1', [], $ctx);

        // Re-seed state then replay same message_id — DepositService returns existing deposit.
        $handler->handle('deposit', [], $ctx);
        $handler->handle('15000', [], $ctx);
        $second = $handler->handle('1', [], $ctx);

        $this->assertSame($first['text'], $second['text']);
        $this->assertDatabaseCount('deposits', 1);
        $this->assertDatabaseCount('pembayarans', 1);
    }

    // -------------------------------------------------------------------------
    // WhatsApp auto-registration tests
    // -------------------------------------------------------------------------

    private function waContext(string $number = '6281234567890', string $msgSuffix = 'reg-1'): array
    {
        return [
            'source'              => 'whatsapp_gateway',
            'external_user_id'    => "whatsapp:{$number}",
            'external_message_id' => "whatsapp:{$msgSuffix}",
            'message_id'          => "whatsapp:{$msgSuffix}",
            'whatsapp'            => $number,
        ];
    }

    public function test_unregistered_user_sees_registration_prompt_on_deposit(): void
    {
        $response = $this->handler()->handle('deposit', [], $this->waContext());

        $this->assertStringContainsString('YA', $response['text']);
        $this->assertStringContainsString('TIDAK', $response['text']);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unregistered_user_can_cancel_registration(): void
    {
        $ctx = $this->waContext();
        $handler = $this->handler();

        // Step 1: deposit triggers prompt and sets state.
        $handler->handle('deposit', [], $ctx);

        // Step 2: TIDAK cancels.
        $response = $handler->handle('tidak', [], $ctx);

        $this->assertStringContainsString('dibatalkan', strtolower($response['text']));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unregistered_user_can_skip_email_and_account_is_created(): void
    {
        $ctx = $this->waContext();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $response = $handler->handle('skip', [], $ctx);

        $this->assertStringContainsString('berhasil dibuat', strtolower($response['text']));
        $this->assertStringContainsString('wa_6281234567890', $response['text']);

        $user = \App\Models\User::where('no_wa', '6281234567890')->first();
        $this->assertNotNull($user);
        $this->assertSame('wa_6281234567890', $user->username);
        $this->assertSame(0, $user->balance);
        $this->assertSame('Member', $user->role);
        $this->assertStringEndsWith('@wa.bot', $user->email); // synthetic placeholder when skipped
        $this->assertNotNull($user->whatsapp_verified_at);
    }

    public function test_unregistered_user_can_register_with_email(): void
    {
        $ctx = $this->waContext();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $response = $handler->handle('user@example.com', [], $ctx);

        $this->assertStringContainsString('berhasil dibuat', strtolower($response['text']));

        $user = \App\Models\User::where('no_wa', '6281234567890')->first();
        $this->assertNotNull($user);
        $this->assertSame('user@example.com', $user->email);
        $this->assertSame(0, $user->balance);
        $this->assertSame('Member', $user->role);
        $this->assertNotNull($user->whatsapp_verified_at);
    }

    public function test_duplicate_email_triggers_retry_prompt(): void
    {
        // Pre-existing user with that email.
        User::factory()->create(['email' => 'taken@example.com']);

        $ctx = $this->waContext();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $response = $handler->handle('taken@example.com', [], $ctx);

        $this->assertStringContainsString('sudah digunakan', strtolower($response['text']));
        // Still only 1 user (the pre-existing one).
        $this->assertDatabaseCount('users', 1);
    }

    public function test_invalid_email_format_triggers_retry_prompt(): void
    {
        $ctx = $this->waContext();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $response = $handler->handle('not-an-email', [], $ctx);

        $this->assertStringContainsString('tidak valid', strtolower($response['text']));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_email_retry_max_three_then_auto_skip(): void
    {
        $ctx = $this->waContext();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $handler->handle('bad1', [], $ctx); // attempt 1
        $handler->handle('bad2', [], $ctx); // attempt 2
        // Third invalid attempt hits max — auto-SKIP.
        $response = $handler->handle('bad3', [], $ctx);

        $this->assertStringContainsString('berhasil dibuat', strtolower($response['text']));

        $user = \App\Models\User::where('no_wa', '6281234567890')->first();
        $this->assertNotNull($user);
        $this->assertStringEndsWith('@wa.bot', $user->email); // synthetic placeholder, no real email provided
    }

    public function test_registered_unverified_user_is_auto_verified_on_deposit(): void
    {
        User::factory()->create(['no_wa' => '6281234567890']); // no whatsapp_verified_at

        $response = $this->handler()->handle('deposit', [], $this->waContext());

        $this->assertStringContainsString('berhasil diverifikasi', strtolower($response['text']));

        $user = \App\Models\User::where('no_wa', '6281234567890')->first();
        $this->assertNotNull($user->whatsapp_verified_at);
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_username_collision_gets_suffix(): void
    {
        // Pre-seed the deterministic username.
        User::factory()->create(['username' => 'wa_6281234567890']);

        $ctx = $this->waContext();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $response = $handler->handle('skip', [], $ctx);

        $this->assertStringContainsString('berhasil dibuat', strtolower($response['text']));

        $newUser = \App\Models\User::where('no_wa', '6281234567890')->first();
        $this->assertNotNull($newUser);
        $this->assertStringStartsWith('wa_6281234567890_', $newUser->username);
    }

    public function test_unregistered_sender_is_denied_before_gateway_work(): void
    {
        // Unregistered sender now gets a registration prompt, not a hard deny.
        $response = $this->handler()->handle('deposit', [], [
            'source'              => 'whatsapp_gateway',
            'external_user_id'   => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:message-1',
            'message_id'          => 'whatsapp:message-1',
            'whatsapp'            => '6281234567890',
        ]);

        $this->assertStringContainsString('YA', $response['text']);
        $this->assertStringContainsString('TIDAK', $response['text']);
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_registered_unverified_sender_is_denied(): void
    {
        // Unverified sender now gets auto-verified, not a hard deny.
        User::factory()->create(['no_wa' => '6281234567890']);

        $response = $this->handler()->handle('deposit', [], [
            'source'              => 'whatsapp_gateway',
            'external_user_id'   => 'whatsapp:6281234567890',
            'external_message_id' => 'whatsapp:message-2',
            'message_id'          => 'whatsapp:message-2',
            'whatsapp'            => '6281234567890',
        ]);

        $this->assertStringContainsString('berhasil diverifikasi', strtolower($response['text']));
        $this->assertDatabaseCount('deposits', 0);
    }
}
