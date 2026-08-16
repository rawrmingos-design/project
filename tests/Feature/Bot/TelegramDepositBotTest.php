<?php

namespace Tests\Feature\Bot;

use App\Http\Controllers\TriPayController;
use App\Models\InboundSourcePolicy;
use App\Models\Method;
use App\Models\TelegramIdentity;
use App\Models\Tenant;
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
use App\Services\Telegram\TelegramUserResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramDepositBotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.telegram-bot-api.deposit_enabled' => true]);
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'disabled',
            'is_active' => true,
        ]);

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

    public function test_unlinked_telegram_identity_is_denied_before_gateway_work(): void
    {
        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldNotReceive('request');
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], $this->context());

        $this->assertStringContainsString('belum tertaut', strtolower($response['text']));
        $this->assertStringContainsString('YA', $response['text']);
        $this->assertStringContainsString('TIDAK', $response['text']);
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_revoked_telegram_identity_is_denied_before_gateway_work(): void
    {
        $this->linkedUser(revoked: true);
        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldNotReceive('request');
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], $this->context());

        $this->assertStringContainsString('belum tertaut', strtolower($response['text']));
        $this->assertStringContainsString('YA', $response['text']);
        $this->assertStringContainsString('TIDAK', $response['text']);
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_cross_tenant_telegram_identity_is_denied_before_gateway_work(): void
    {
        $user = $this->linkedUser();
        $owner = User::factory()->create();
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Other Tenant',
            'subdomain' => 'other-tenant',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        TelegramIdentity::query()
            ->where('user_id', $user->id)
            ->update(['tenant_id' => $tenant->id]);
        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldNotReceive('request');
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], $this->context());

        $this->assertStringContainsString('belum dapat diverifikasi', strtolower($response['text']));
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_linked_telegram_identity_creates_deposit_for_resolved_user(): void
    {
        $owner = User::factory()->create();
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Deposit Tenant',
            'subdomain' => 'deposit-tenant',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $user = $this->linkedUser(tenantId: $tenant->id);
        $this->fakeSuccessfulGateway('T-TELEGRAM-1');

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], $this->context());

        $this->assertStringContainsString('Kode Bayar / VA', $response['text']);
        $this->assertDatabaseHas('deposits', [
            'tenant_id' => $tenant->id,
            'username' => $user->username,
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:primary:9876',
            'external_message_id' => 'telegram:primary:12345:111',
        ]);
        $this->assertDatabaseHas('pembayarans', [
            'tenant_id' => $tenant->id,
            'reference' => 'T-TELEGRAM-1',
        ]);
        $deposit = DB::table('deposits')->first();
        $metadata = json_decode($deposit->payment_metadata, true);
        $this->assertSame('primary', data_get($metadata, 'telegram_bot_scope'));
        $this->assertSame(55, data_get($metadata, 'telegram_update_id'));
        $this->assertSame(64, strlen((string) data_get($metadata, 'telegram_chat_fingerprint')));
        $this->assertArrayNotHasKey('telegram_chat_id', $metadata);
    }

    public function test_telegram_deposit_rejects_invalid_amount_and_method_before_gateway_work(): void
    {
        $this->linkedUser();
        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldNotReceive('request');
        });

        $invalidAmount = $this->handler()->handle('deposit', ['9999', 'BCA'], $this->context());
        $invalidMethod = $this->handler()->handle('deposit', ['15000', 'UNKNOWN'], $this->context());

        $this->assertStringContainsString('minimal deposit', strtolower($invalidAmount['text']));
        $this->assertStringContainsString('metode pembayaran tidak valid', strtolower($invalidMethod['text']));
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_telegram_deposit_requires_message_id(): void
    {
        $this->linkedUser();
        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldNotReceive('request');
        });

        $context = $this->context();
        unset($context['message_id']);
        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], $context);

        $this->assertStringContainsString('ID yang valid', $response['text']);
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_telegram_deposit_returns_qr_media_without_checkout_url(): void
    {
        $this->linkedUser();
        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldReceive('request')->once()->andReturn([
                'success' => true,
                'amount' => 15000,
                'payment_code' => 'QRIS-PAYLOAD',
                'qr_url' => 'https://cdn.example.test/qr/telegram.png',
                'pay_url' => 'https://tripay.co.id/checkout/should-not-be-sent',
                'reference' => 'T-TELEGRAM-QR',
                'expired_at' => time() + 3600,
            ]);
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], $this->context());

        $this->assertSame('https://cdn.example.test/qr/telegram.png', $response['photo_url']);
        $this->assertStringNotContainsString('tripay.co.id/checkout', $response['text']);
    }

    public function test_provider_failure_returns_generic_deposit_failure_without_persistence(): void
    {
        $this->linkedUser();
        $this->mock(TriPayController::class, function ($mock): void {
            $mock->shouldReceive('request')->once()->andReturn([
                'success' => false,
                'msg' => 'provider secret error',
            ]);
        });

        $response = $this->handler()->handle('deposit', ['15000', 'BCA'], $this->context());

        $this->assertSame('Gagal membuat invoice via Tripay', $response['text']);
        $this->assertStringNotContainsString('provider secret error', $response['text']);
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_duplicate_update_is_rejected_before_second_deposit_mutation(): void
    {
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.webhook_secret' => 'dummy-secret',
            'services.telegram-bot-api.bot_scope' => 'primary',
        ]);
        $this->linkedUser();
        $this->fakeSuccessfulGateway('T-TELEGRAM-UPDATE');
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);
        $payload = [
            'update_id' => 998877,
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876, 'username' => 'informational-only'],
                'text' => 'deposit 15000 BCA',
                'message_id' => 111,
            ],
        ];
        $headers = ['X-Telegram-Bot-Api-Secret-Token' => 'dummy-secret'];

        $this->postJson('/api/webhooks/bot/telegram', $payload, $headers)->assertOk();
        $this->postJson('/api/webhooks/bot/telegram', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('status', 'duplicate');

        $this->assertDatabaseCount('deposits', 1);
        $this->assertDatabaseCount('pembayarans', 1);
    }

    public function test_duplicate_telegram_message_replays_the_same_deposit(): void
    {
        $this->linkedUser();
        $this->fakeSuccessfulGateway('T-TELEGRAM-DUP');
        $context = $this->context();

        $first = $this->handler()->handle('deposit', ['15000', 'BCA'], $context);
        $second = $this->handler()->handle('deposit', ['15000', 'BCA'], $context);

        $this->assertSame($first['text'], $second['text']);
        $this->assertDatabaseCount('deposits', 1);
        $this->assertDatabaseCount('pembayarans', 1);
    }

    private function linkedUser(bool $revoked = false, ?int $tenantId = null): User
    {
        $user = User::factory()->create(['tenant_id' => $tenantId]);
        TelegramIdentity::query()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenantId,
            'bot_scope' => 'primary',
            'telegram_user_id' => '9876',
            'chat_id' => '12345',
            'linked_at' => now(),
            'verified_at' => now(),
            'revoked_at' => $revoked ? now() : null,
        ]);

        return $user;
    }

    private function fakeSuccessfulGateway(string $reference): void
    {
        $this->mock(TriPayController::class, function ($mock) use ($reference): void {
            $mock->shouldReceive('request')->once()->andReturn([
                'success' => true,
                'amount' => 15000,
                'payment_code' => '1234567890',
                'pay_url' => 'https://tripay.co.id/checkout/' . $reference,
                'reference' => $reference,
                'expired_at' => time() + 3600,
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Telegram auto-registration tests
    // -------------------------------------------------------------------------

    public function test_unlinked_telegram_user_can_cancel_registration(): void
    {
        $ctx = $this->context();
        $handler = $this->handler();

        // Step 1: deposit triggers prompt
        $handler->handle('deposit', [], $ctx);

        // Step 2: TIDAK cancels.
        $response = $handler->handle('tidak', [], $ctx);

        $this->assertStringContainsString('dibatalkan', strtolower($response['text']));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unlinked_telegram_user_can_skip_email_and_account_is_created(): void
    {
        $ctx = $this->context();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $handler->handle('myuser123', [], $ctx);
        $response = $handler->handle('skip', [], $ctx);

        $this->assertStringContainsString('berhasil dibuat', strtolower($response['text']));
        $this->assertStringContainsString('myuser123', $response['text']);

        $user = User::where('username', 'myuser123')->first();
        $this->assertNotNull($user);
        $this->assertSame(0, $user->balance);
        $this->assertSame('Member', $user->role);
        $this->assertStringEndsWith('@tg.bot', $user->email);

        $identity = \App\Models\TelegramIdentity::where('user_id', $user->id)->first();
        $this->assertNotNull($identity);
        $this->assertSame('9876', $identity->telegram_user_id);
    }

    public function test_unlinked_telegram_user_can_register_with_email(): void
    {
        $ctx = $this->context();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $handler->handle('myuser123', [], $ctx);
        $response = $handler->handle('test@example.com', [], $ctx);

        $this->assertStringContainsString('berhasil dibuat', strtolower($response['text']));

        $user = User::where('username', 'myuser123')->first();
        $this->assertNotNull($user);
        $this->assertSame(0, $user->balance);
        $this->assertSame('Member', $user->role);
        $this->assertSame('test@example.com', $user->email);
    }

    public function test_telegram_username_validation_and_uniqueness(): void
    {
        User::factory()->create(['username' => 'takenuser']);

        $ctx = $this->context();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);

        // Invalid format
        $response = $handler->handle('bad name', [], $ctx);
        $this->assertStringContainsString('tidak valid', strtolower($response['text']));

        // Taken username
        $response = $handler->handle('takenuser', [], $ctx);
        $this->assertStringContainsString('sudah digunakan', strtolower($response['text']));

        // Valid username
        $response = $handler->handle('gooduser123', [], $ctx);
        $this->assertStringContainsString('diterima', strtolower($response['text']));
        $this->assertStringContainsString('email', strtolower($response['text']));
    }

    public function test_telegram_username_retry_max_three_then_cancel(): void
    {
        $ctx = $this->context();
        $handler = $this->handler();

        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);

        $handler->handle('bad name1', [], $ctx); // attempt 1
        $handler->handle('bad name2', [], $ctx); // attempt 2
        // Third invalid attempt hits max — cancel.
        $response = $handler->handle('bad name3', [], $ctx);

        $this->assertStringContainsString('dibatalkan', strtolower($response['text']));
        $this->assertDatabaseCount('users', 0);
    }

    public function test_newly_registered_telegram_user_is_not_prompted_to_register_again(): void
    {
        $ctx = $this->context();
        $handler = $this->handler();

        // Complete registration flow.
        $handler->handle('deposit', [], $ctx);
        $handler->handle('ya', [], $ctx);
        $handler->handle('myuser123', [], $ctx);
        $handler->handle('skip', [], $ctx);

        // Bug #1 regression: verified_at and linked_at must be set on the created identity.
        $user = User::where('username', 'myuser123')->first();
        $this->assertNotNull($user);
        $identity = \App\Models\TelegramIdentity::where('user_id', $user->id)->first();
        $this->assertNotNull($identity, 'TelegramIdentity must exist after registration');
        $this->assertNotNull($identity->verified_at, 'verified_at must be set — otherwise resolver returns STATUS_REVOKED and registration loops');
        $this->assertNotNull($identity->linked_at, 'linked_at must be set');

        // Second deposit attempt must NOT show registration prompt again.
        // (deposit itself may fail without a tenant/tripay setup, but the key assertion is no YA/TIDAK prompt)
        $response = $handler->handle('deposit', ['15000', 'BCA'], $ctx);

        $this->assertStringNotContainsString('YA', $response['text']);
        $this->assertStringNotContainsString('TIDAK', $response['text']);
        $this->assertStringNotContainsString('belum tertaut', strtolower($response['text']));
    }

    private function context(): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'telegram_user_id' => 9876,
            'telegram_bot_scope' => 'primary',
            'telegram_chat_id' => 12345,
            'telegram_message_id' => 111,
            'telegram_update_id' => 55,
            'telegram_metadata' => ['username' => 'informational-only'],
            'message_id' => 'telegram:primary:12345:111',
            'correlation_id' => 'corr-test',
        ];
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
            null,
            null,
            app(DepositService::class),
            null,
            app(TelegramUserResolver::class),
        );
    }
}
