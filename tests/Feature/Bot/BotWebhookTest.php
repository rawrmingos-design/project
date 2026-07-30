<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\CustomInput;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\Gateway\GatewayCheckIdService;
use App\Services\Gateway\GatewayInvoiceService;
use App\Services\Gateway\GatewayPricingService;
use App\Services\PaymentMethodCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery\MockInterface;
use Tests\TestCase;

class BotWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.telegram-bot-api.token' => 'dummy-token']);
    }

    public function test_telegram_adapter_handles_menu_command_and_replies_with_buttons()
    {
        CategoryType::query()->create([
            'name' => '🎮 Top Up',
            'slug' => 'top-up',
            'sort' => 1
        ]);

        // Kita butuh 1 kategori & layanan agar categoryType tidak kosong (terfilter di GatewayCatalogService)
        $kategori = Kategori::factory()->create([
            'category_type_id' => 1,
            'kode' => 'mlbb',
            'status' => 'active'
        ]);
        Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'status' => 'available'
        ]);

        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => '/menu',
                'message_id' => 111,
            ]
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                   $request['chat_id'] === 12345 &&
                   str_contains($request['text'], 'Pilih kategori') &&
                   isset($request['reply_markup']['inline_keyboard']);
        });
    }

    public function test_telegram_adapter_handles_callback_query()
    {
        Http::fake([
            'https://api.telegram.org/botdummy-token/answerCallbackQuery' => Http::response(['ok' => true]),
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'callback_query' => [
                'id' => 'cb_123',
                'from' => ['id' => 9876],
                'message' => [
                    'chat' => ['id' => 12345],
                    'message_id' => 111,
                ],
                'data' => 'kategori top-up',
            ]
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'sendMessage') &&
                   $request['chat_id'] === 12345;
        });
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'answerCallbackQuery') &&
                   $request['callback_query_id'] === 'cb_123';
        });
    }

    public function test_telegram_menu_paginates_categories_and_preserves_navigation_rows()
    {
        Cache::flush();

        foreach (range(1, 9) as $index) {
            $type = CategoryType::query()->create([
                'name' => "Top Up {$index}",
                'slug' => "top-up-{$index}",
                'sort' => $index,
            ]);
            $category = Kategori::factory()->create([
                'category_type_id' => $type->id,
                'status' => 'active',
            ]);
            Layanan::factory()->create([
                'kategori_id' => $category->id,
                'status' => 'available',
            ]);
        }

        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'menu',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $keyboard = $request['reply_markup']['inline_keyboard'];
            $callbacks = collect($keyboard)->flatten(1)->pluck('callback_data');

            return str_contains($request['text'], 'Halaman 1/2')
                && count($keyboard) === 5
                && count($keyboard[0]) === 2
                && $keyboard[0][0]['text'] === '🎮 Top Up 1'
                && $callbacks->contains('menu page:2')
                && $callbacks->every(fn (string $callback): bool => strlen($callback) <= 64);
        });
    }

    public function test_telegram_product_list_adds_game_emoji_and_back_button()
    {
        $type = CategoryType::query()->create([
            'name' => 'Top Up Games',
            'slug' => 'top-up-games',
            'sort' => 1,
        ]);
        $category = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'nama' => 'Mobile Legends',
            'kode' => 'mlbb',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'status' => 'available',
        ]);
        $secondCategory = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'nama' => 'Free Fire',
            'kode' => 'free-fire',
            'status' => 'active',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $secondCategory->id,
            'status' => 'available',
        ]);

        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'kategori top-up-games',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $keyboard = $request['reply_markup']['inline_keyboard'];

            return count($keyboard[0]) === 2
                && $keyboard[0][0]['text'] === '🔫 Free Fire'
                && $keyboard[0][1]['text'] === '⚔️ Mobile Legends'
                && $keyboard[array_key_last($keyboard)][0]['text'] === '🔙 Kembali'
                && $keyboard[array_key_last($keyboard)][0]['callback_data'] === 'menu';
        });
    }

    public function test_payment_methods_wrap_two_buttons_per_row()
    {
        $response = app(BotMessageFormatter::class)->formatPaymentMethods([
            'ok' => true,
            'data' => [
                ['name' => 'QRIS', 'code' => 'QRIS'],
                ['name' => 'BCA Virtual Account', 'code' => 'BCAVA'],
                ['name' => 'GoPay', 'code' => 'GOPAY'],
            ],
        ], 123, 1, 'layanan mlbb');

        $buttons = $response['buttons'];

        $this->assertCount(2, $buttons[0]);
        $this->assertSame('💳 QRIS', $buttons[0][0]['text']);
        $this->assertSame('💳 BCA Virtual Account', $buttons[0][1]['text']);
        $this->assertCount(1, $buttons[1]);
        $this->assertSame('💳 GoPay', $buttons[1][0]['text']);
        $this->assertSame('🔙 Kembali', $buttons[2][0]['text']);
        $this->assertSame('layanan mlbb', $buttons[2][0]['callback']);
    }

    public function test_telegram_service_list_wraps_two_buttons_per_row()
    {
        $type = CategoryType::query()->create([
            'name' => 'Top Up Games',
            'slug' => 'top-up-games',
            'sort' => 1,
        ]);
        $category = Kategori::factory()->create([
            'category_type_id' => $type->id,
            'kode' => 'mlbb',
            'nama' => 'Mobile Legends',
            'status' => 'active',
        ]);

        foreach (range(1, 9) as $index) {
            Layanan::factory()->create([
                'kategori_id' => $category->id,
                'layanan' => "{$index} Diamond",
                'harga_member' => $index * 1000,
                'status' => 'available',
            ]);
        }

        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'layanan mlbb',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            $keyboard = $request['reply_markup']['inline_keyboard'];

            $paginationRow = $keyboard[array_key_last($keyboard) - 1];
            $backRow = $keyboard[array_key_last($keyboard)];

            return count($keyboard[0]) === 2
                && $keyboard[0][0]['text'] === '💎 1 Diamond · Rp 1.000'
                && $keyboard[0][1]['text'] === '💎 2 Diamond · Rp 2.000'
                && count($paginationRow) === 1
                && $paginationRow[0]['text'] === 'Next ➡️'
                && count($backRow) === 1
                && $backRow[0]['text'] === '🔙 Kembali'
                && $backRow[0]['callback_data'] === 'kategori top-up-games';
        });
    }

    public function test_fonnte_adapter_handles_layanan_command_and_appends_fallback_buttons()
    {
        Http::fake();

        $category = Kategori::factory()->create([
            'kode' => 'mlbb',
            'nama' => 'Mobile Legends',
            'status' => 'active'
        ]);
        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '100 DM',
            'harga_member' => 15000,
            'status' => 'available'
        ]);

        $response = $this->postJson('/api/webhooks/bot/fonnte', [
            'sender' => '6281234567890',
            'message' => 'layanan mlbb',
            'id' => 'MSG123',
        ]);

        $response->assertOk()->assertJsonPath('status', true);
    }

    public function test_telegram_status_renders_verified_payment_message_with_configured_store_name(): void
    {
        config(['app.name' => 'Z_Vault *Store*']);

        $response = app(BotMessageFormatter::class)->formatStatus([
            'ok' => true,
            'data' => [
                'order_id' => 'INV_[123]',
                'product' => 'Mobile *Legends*',
                'nickname' => 'Player',
                'amount' => 10000,
                'status' => 'Pending',
                'payment' => ['status' => 'Lunas'],
                'sn' => null,
            ],
        ]);

        $this->assertSame("✅ *PEMBAYARAN BERHASIL DIVERIFIKASI!*\n\nTerima kasih telah berbelanja di Z\\_Vault \\*Store\\*.\n\n🧾 *RINCIAN TRANSAKSI*\n├ Nomor Invoice: *INV\\_\\[123\\]*\n└ Produk: *Mobile \\*Legends\\**\n\n🔐 Jika ada kendala hubungi admin utama:\nchat admin @mings dan kirimkan id pesanan nya", $response['text']);
        $this->assertSame('🔙 Kembali ke Menu', $response['buttons'][0][0]['text']);
    }

    public function test_telegram_status_keeps_generic_template_when_payment_is_not_verified(): void
    {
        $response = app(BotMessageFormatter::class)->formatStatus([
            'ok' => true,
            'data' => [
                'order_id' => 'INV-123',
                'product' => 'Mobile Legends',
                'nickname' => 'Player',
                'amount' => 10000,
                'status' => 'Success',
                'payment' => ['status' => 'Belum Lunas'],
                'sn' => null,
            ],
        ]);

        $this->assertStringNotContainsString('PEMBAYARAN BERHASIL DIVERIFIKASI', $response['text']);
        $this->assertStringContainsString('*Status Pesanan*', $response['text']);
        $this->assertStringContainsString('Status Pembayaran: *Belum Lunas*', $response['text']);
        $this->assertStringContainsString('Status Pesanan: *Success*', $response['text']);
    }

    public function test_telegram_checkout_mentions_configured_zone_field_on_quote_and_retry()
    {
        Cache::flush();
        $service = $this->createConversationService('mobile-legends', true, 123);
        CustomInput::query()->updateOrCreate([
            'kategori_id' => (string) $service->kategori_id,
        ], [
            'field_1' => 'Game_ID,Masukkan Game_ID,number',
            'field_2' => 'Server_ID,Masukkan Server_ID,number',
        ]);
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];
        $customInputs = app(\App\Support\CustomInputDefaults::class)->inputSpecification(
            Kategori::query()->findOrFail($service->kategori_id),
        );
        $this->assertSame('Game_ID', $customInputs['user_id']['label']);
        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock) use ($service, $customInputs): void {
            $mock->shouldReceive('quote')->once()->andReturn($this->fakePriceQuote(
                serviceId: $service->id,
                categoryCode: 'mobile-legends',
                requiresZoneId: true,
                customInputs: $customInputs,
            ));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $quote = $handler->handle('harga', [(string) $service->id, 'QRIS'], $context);
        $retry = $handler->handle('12345', [], $context);

        $this->assertStringContainsString('Game\\_ID', $quote['text']);
        $this->assertStringContainsString('Server\\_ID', $quote['text']);
        $this->assertStringContainsString('Game\\_ID', $retry['text']);
        $this->assertStringContainsString('Server\\_ID', $retry['text']);
    }

    public function test_telegram_checkout_uses_current_service_zone_requirement()
    {
        Cache::flush();
        $category = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'server_id' => true,
            'status' => 'active',
        ]);
        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'status' => 'available',
        ]);
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];
        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock) use ($service): void {
            $mock->shouldReceive('quote')->once()->andReturn($this->fakePriceQuote(
                serviceId: $service->id,
                categoryCode: 'mobile-legends',
                requiresZoneId: false,
            ));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(fn (array $payload): bool => $payload['uid'] === '12345' && $payload['zone'] === '6789')
                ->andReturn($this->fakeInvoiceResponse());
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $handler->handle('harga', [(string) $service->id, 'QRIS'], $context);
        $invalid = $handler->handle('12345', [], $context);
        $success = $handler->handle('12345', ['6789'], $context);

        $this->assertStringContainsString('UID <Server ID>', $invalid['text']);
        $this->assertSame('*⏳ MENUNGGU PEMBAYARAN*', strtok($success['text'], "\n"));
    }

    public function test_telegram_checkout_validates_select_zone_values_and_supports_spaces()
    {
        Cache::flush();
        $service = $this->createConversationService('mobile-legends', true, 123);
        CustomInput::query()->updateOrCreate([
            'kategori_id' => (string) $service->kategori_id,
        ], [
            'field_1' => 'User ID,Masukkan User ID,number',
            'field_2' => 'Region,Pilih Region,select',
            'field_select_title' => 'Asia Tenggara,Eropa',
            'field_select' => 'asia tenggara,eropa',
        ]);
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];
        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock) use ($service): void {
            $mock->shouldReceive('quote')->once()->andReturn($this->fakePriceQuote(
                serviceId: $service->id,
                categoryCode: 'mobile-legends',
                requiresZoneId: true,
                customInputs: app(\App\Support\CustomInputDefaults::class)->inputSpecification(
                    Kategori::query()->findOrFail($service->kategori_id),
                ),
            ));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(fn (array $payload): bool => $payload['uid'] === '12345' && $payload['zone'] === 'asia tenggara')
                ->andReturn($this->fakeInvoiceResponse());
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $quote = $handler->handle('harga', [(string) $service->id, 'QRIS'], $context);
        $success = $handler->handle('12345', ['asia', 'tenggara'], $context);

        $this->assertStringContainsString('Asia Tenggara: `asia tenggara`', $quote['text']);
        $this->assertSame('*⏳ MENUNGGU PEMBAYARAN*', strtok($success['text'], "\n"));
    }

    public function test_telegram_checkout_uses_zoneless_resolver_rule()
    {
        Cache::flush();
        $category = Kategori::factory()->create([
            'kode' => 'free-fire',
            'server_id' => true,
            'status' => 'active',
        ]);
        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'status' => 'available',
        ]);
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];
        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock) use ($service): void {
            $mock->shouldReceive('quote')->once()->andReturn($this->fakePriceQuote(
                serviceId: $service->id,
                categoryCode: 'free-fire',
                requiresZoneId: true,
            ));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(fn (array $payload): bool => $payload['uid'] === '12345' && $payload['zone'] === null)
                ->andReturn($this->fakeInvoiceResponse());
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $handler->handle('harga', [(string) $service->id, 'QRIS'], $context);
        $success = $handler->handle('12345', [], $context);

        $this->assertSame('*⏳ MENUNGGU PEMBAYARAN*', strtok($success['text'], "\n"));
    }

    public function test_telegram_checkout_state_creates_invoice_from_uid_and_zone_reply()
    {
        Cache::flush();
        $this->createConversationService('mobile-legends', true, 123);
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];
        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->with([
                'service_id' => '123',
                'payment_method' => 'QRIS',
            ], null)->andReturn($this->fakePriceQuote(requiresZoneId: true));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(function (array $payload, $user, string $source, array $invoiceContext): bool {
                    return $user === null
                        && $source === 'telegram_gateway'
                        && $payload['service_id'] === '123'
                        && $payload['payment_method'] === 'QRIS'
                        && $payload['uid'] === '12345'
                        && $payload['zone'] === '6789'
                        && $payload['email'] === '9876@telegram.user'
                        && $invoiceContext === $payload;
                })
                ->andReturn($this->fakeInvoiceResponse());
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $quote = $handler->handle('harga', ['123', 'QRIS'], $context);
        $invoiceResponse = $handler->handle('12345', ['6789'], $context);

        $this->assertStringContainsString('Silahkan balas pesan ini dengan User ID dan Server ID', $quote['text']);
        $this->assertSame('*⏳ MENUNGGU PEMBAYARAN*', strtok($invoiceResponse['text'], "\n"));
        $this->assertNull(Cache::get($this->checkoutStateKey('telegram:9876')));
    }

    public function test_telegram_checkout_state_survives_invalid_input_and_clears_on_cancel()
    {
        Cache::flush();
        $this->createConversationService('mobile-legends', true, 123);
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];
        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->andReturn($this->fakePriceQuote(requiresZoneId: true));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $handler->handle('harga', ['123', 'QRIS'], $context);
        $invalid = $handler->handle('12345', [], $context);

        $this->assertStringContainsString('Format ID belum sesuai', $invalid['text']);
        $this->assertNotNull(Cache::get($this->checkoutStateKey('telegram:9876')));

        $cancelled = $handler->handle('batal', [], $context);

        $this->assertSame('Checkout dibatalkan.', $cancelled['text']);
        $this->assertNull(Cache::get($this->checkoutStateKey('telegram:9876')));
    }

    public function test_telegram_invoice_uses_synthetic_email_contact()
    {
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(function (array $payload, $user, string $source, array $context): bool {
                    return $user === null
                        && $payload['email'] === '9876@telegram.user'
                        && $context['email'] === '9876@telegram.user'
                        && $source === 'telegram_gateway';
                })
                ->andReturn($this->fakeInvoiceResponse());
        });

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ]
        ]);

        $response->assertOk();
    }

    public function test_telegram_invoice_sends_qris_image_when_provider_returns_qris_url()
    {
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendPhoto' => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse('https://provider.example/qris/INV-1.png'));
        });

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request): bool {
            $keyboard = $request['reply_markup']['inline_keyboard'];

            return str_contains($request->url(), 'sendPhoto')
                && $request['photo'] === 'https://provider.example/qris/INV-1.png'
                && str_contains($request['caption'], '⏳ MENUNGGU PEMBAYARAN')
                && str_contains($request['caption'], 'No. Invoice: `INV-1`')
                && str_contains($request['caption'], 'Produk: Mobile Legends (Top Up Games)')
                && str_contains($request['caption'], 'Jumlah: x1')
                && str_contains($request['caption'], 'Total Tagihan: Rp 10.000 (Termasuk Admin)')
                && str_contains($request['caption'], 'Silakan scan QRIS atau gunakan nomor VA di atas.')
                && ! str_contains($request['caption'], 'Kode Bayar / VA')
                && ! str_contains($request['caption'], 'Link Pembayaran:')
                && $keyboard[0][0]['text'] === '🔗 Buka Halaman Invoice'
                && $keyboard[0][0]['url'] === 'https://pay.example/inv-1'
                && $keyboard[1][0]['text'] === '🔎 Cek Status Pembayaran'
                && isset($keyboard[1][0]['callback_data']);
        });
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'sendMessage'));
    }

    public function test_telegram_invoice_sends_qris_image_when_payment_code_is_direct_image_url()
    {
        $qrisUrl = 'https://assets-b3xk0.b-cdn.net/2026/07/TP260729IGRQ307771.png';
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendPhoto' => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($qrisUrl): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: $qrisUrl));
        });

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) use ($qrisUrl): bool {
            $keyboard = $request['reply_markup']['inline_keyboard'];

            return str_contains($request->url(), 'sendPhoto')
                && $request['photo'] === $qrisUrl
                && ! str_contains($request['caption'], 'Kode Bayar / VA')
                && $keyboard[0][0]['url'] === 'https://pay.example/inv-1'
                && isset($keyboard[1][0]['callback_data']);
        });
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'sendMessage'));
    }

    public function test_telegram_invoice_hides_raw_qris_payload_and_sends_generated_qr_photo()
    {
        $rawQris = '00020101021226610014COM.GO-JEK.WWW01189360091430274901050210G1234567890303UMI51440014ID.CO.QRIS.WWW0215ID10200423000030303UMI5204541153033605802ID5910Test Store6007Jakarta6105123456304ABCD';
        Http::fake([
            'https://api.qrserver.com/v1/create-qr-code/*' => Http::response('image', 200),
            'https://api.telegram.org/botdummy-token/sendPhoto' => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($rawQris): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: $rawQris));
        });

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) use ($rawQris): bool {
            return str_contains($request->url(), 'sendPhoto')
                && str_starts_with($request['photo'], 'https://api.qrserver.com/v1/create-qr-code/')
                && ! str_contains($request['caption'], $rawQris)
                && ! str_contains($request['caption'], 'Kode Bayar / VA');
        });
    }

    public function test_telegram_invoice_shows_text_payment_code_and_invoice_url_button()
    {
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: '1234567890123456'));
        });

        $response = $this->postJson('/api/webhooks/bot/telegram', [
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 BCA user123 zone123',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();

        Http::assertSent(function ($request): bool {
            $keyboard = $request['reply_markup']['inline_keyboard'];

            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], '⏳ MENUNGGU PEMBAYARAN')
                && str_contains($request['text'], 'No. Invoice: `INV-1`')
                && str_contains($request['text'], 'Produk: Mobile Legends (Top Up Games)')
                && str_contains($request['text'], 'Jumlah: x1')
                && str_contains($request['text'], 'Total Tagihan: Rp 10.000 (Termasuk Admin)')
                && str_contains($request['text'], 'Kode Bayar / VA: `1234567890123456`')
                && str_contains($request['text'], 'Silakan scan QRIS atau gunakan nomor VA di atas.')
                && ! str_contains($request['text'], 'Link Pembayaran:')
                && $keyboard[0][0]['url'] === 'https://pay.example/inv-1'
                && isset($keyboard[1][0]['callback_data']);
        });
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'sendPhoto'));
    }

    public function test_fonnte_invoice_uses_sender_as_whatsapp_contact()
    {
        Http::fake();

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(function (array $payload, $user, string $source, array $context): bool {
                    return $user === null
                        && $payload['whatsapp'] === '6281234567890'
                        && $context['whatsapp'] === '6281234567890'
                        && $source === 'whatsapp_gateway';
                })
                ->andReturn($this->fakeInvoiceResponse());
        });

        $response = $this->postJson('/api/webhooks/bot/fonnte', [
            'sender' => '+62 812-3456-7890',
            'message' => 'invoice 1 QRIS user123 zone123',
            'id' => 'MSG123',
        ]);

        $response->assertOk()->assertJsonPath('status', true);
    }

    private function createConversationService(string $categoryCode, bool $requiresZoneId, int $serviceId): Layanan
    {
        $category = Kategori::factory()->create([
            'kode' => $categoryCode,
            'server_id' => $requiresZoneId,
            'status' => 'active',
        ]);

        return Layanan::factory()->create([
            'id' => $serviceId,
            'kategori_id' => $category->id,
            'status' => 'available',
        ]);
    }

    private function makeBotCommandHandler(GatewayPricingService $pricing, GatewayInvoiceService $invoice): BotCommandHandler
    {
        return new BotCommandHandler(
            app(GatewayCatalogService::class),
            app(PaymentMethodCatalogService::class),
            $pricing,
            app(GatewayCheckIdService::class),
            $invoice,
            app(BotMessageFormatter::class),
        );
    }

    private function fakePriceQuote(
        bool $requiresZoneId,
        int $serviceId = 123,
        string $categoryCode = 'mlbb',
        string $categoryName = 'Mobile Legends',
        array $customInputs = [],
    ): array {
        return [
            'ok' => true,
            'data' => [
                'service_id' => $serviceId,
                'service_name' => '100 Diamond',
                'category_code' => $categoryCode,
                'category_name' => $categoryName,
                'requires_zone_id' => $requiresZoneId,
                'custom_inputs' => $customInputs,
                'base_amount' => 10000,
                'discount' => 0,
                'payment_fee' => 0,
                'total_amount' => 10000,
                'payment_method' => [
                    'code' => 'QRIS',
                    'name' => 'QRIS',
                ],
            ],
        ];
    }

    private function checkoutStateKey(string $externalUserId): string
    {
        return 'bot:checkout-state:' . hash('sha256', $externalUserId);
    }

    private function fakeInvoiceResponse(?string $qrisUrl = null, string $paymentCode = 'QRIS-1'): array
    {
        return [
            'ok' => true,
            'message' => 'Invoice berhasil dibuat.',
            'data' => array_filter([
                'order_id' => 'INV-1',
                'service_name' => 'Mobile Legends',
                'category_name' => 'Top Up Games',
                'quantity' => 1,
                'payment_url' => 'https://pay.example/inv-1',
                'qris_url' => $qrisUrl,
                'payment' => [
                    'payment_code' => $paymentCode,
                    'amount' => 10000,
                ],
            ]),
        ];
    }
}
