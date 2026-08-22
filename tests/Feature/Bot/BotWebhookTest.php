<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\CustomInput;
use App\Models\Kategori;
use App\Models\InboundSourcePolicy;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\User;
use App\Services\Bot\BotCommandHandler;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\TelegramChannelMembershipService;
use App\Services\Gateway\GatewayCatalogService;
use App\Services\WhatsappNotificationService;
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
        config(['services.telegram-bot-api.webhook_secret' => 'dummy-secret']);
        config(['services.telegram-bot-api.deposit_enabled' => false]);
        config(['services.telegram-bot-api.order_enabled' => true]);
        config(['bot.order_wa_enabled' => true]);
        config(['services.fonnte.device_token' => 'dummy-device-token']);

        // Seed setting_webs so AppServiceProvider DB override sets order flags correctly.
        // Without this, AppServiceProvider reads bot_order_tg_enabled=false from DB
        // during HTTP requests, overriding the config() calls above.
        \Illuminate\Support\Facades\DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'Test Store',
                'deskripsi_web' => 'Test Description',
                'keywords' => 'test',
                'url_wa' => 'https://wa.me/628123456789',
                'url_ig' => 'https://instagram.com/test',
                'url_tiktok' => 'https://tiktok.com/@test',
                'url_youtube' => 'https://youtube.com/test',
                'url_fb' => 'https://facebook.com/test',
                'topupindo_api' => 'test',
                'warna1' => '#000000',
                'warna2' => '#000000',
                'warna3' => '#000000',
                'warna4' => '#000000',
                'paydisini_apikey' => 'test',
                'order_prefik' => 'TRX',
                'nomor_admin' => '628123456789',
                'bot_order_tg_enabled' => 1,
                'bot_order_wa_enabled' => 1,
            ],
        );
        config(['app.name' => 'Test Store']);

        // Mock inbound source policies to allow local requests
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'disabled',
            'is_active' => true,
        ]);
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'fonnte',
            'mode' => 'disabled',
            'is_active' => true,
        ]);
    }

    protected function postJsonTelegram(array $data)
    {
        return parent::postJson('/api/webhooks/bot/telegram', $data, [
            'X-Telegram-Bot-Api-Secret-Token' => config('services.telegram-bot-api.webhook_secret', ''),
        ]);
    }

    protected function postJsonFonnte(array $data)
    {
        return parent::postJson('/api/webhooks/bot/fonnte', $data, [
            'Authorization' => config('services.fonnte.device_token', ''),
        ]);
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

        $response = $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => '/menu',
                'message_id' => 111,
            ]
        ]);

        $response->assertOk();

        Http::assertSent(function ($request) {
            if (! str_contains($request->url(), 'sendMessage')) {
                return false;
            }

            $buttons = collect($request['reply_markup']['inline_keyboard'])->flatten(1);

            return $request['chat_id'] === 12345
                && str_contains($request['text'], 'Selamat datang di Test Store.')
                && str_contains($request['text'], 'Gunakan menu dengan membalas angka yang tersedia.')
                && str_contains($request['text'], 'Jika ada kendala, hubungi admin: 628123456789')
                && str_contains($request['text'], '🏠 *Menu Utama*')
                && $buttons->contains(fn (array $button): bool => ($button['text'] ?? null) === '🏆 Leaderboard'
                    && ($button['callback_data'] ?? null) === 'leaderboard')
                && $buttons->doesntContain(fn (array $button): bool => ($button['text'] ?? null) === '💰 Deposit'
                    || ($button['callback_data'] ?? null) === 'deposit');
        });
    }

    public function test_telegram_deposit_command_is_rejected_while_capability_is_disabled(): void
    {
        config(['services.telegram-bot-api.deposit_enabled' => false]);
        $pricing = $this->mock(GatewayPricingService::class);
        $invoice = $this->mock(GatewayInvoiceService::class);
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $response = $handler->handle('deposit', [], [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'telegram_user_id' => 9876,
        ]);

        $this->assertSame('Deposit belum tersedia melalui gateway ini.', $response['text']);
        $this->assertSame([], $response['buttons']);
    }

    public function test_telegram_help_uses_shared_reply_keyboard_with_history_and_without_disabled_deposit(): void
    {
        config(['services.telegram-bot-api.deposit_enabled' => false]);
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'help',
                'message_id' => 112,
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'sendMessage')) {
                return false;
            }

            $labels = collect($request['reply_markup']['keyboard'])->flatten(1)->pluck('text');

            return $labels->contains('🛍️ Buka Menu')
                && $labels->contains('🏆 Leaderboard')
                && $labels->contains('📜 Riwayat Order')
                && ! $labels->contains('💰 Deposit');
        });
    }

    public function test_telegram_capability_flag_enables_deposit_in_menu_and_reply_keyboard(): void
    {
        config(['services.telegram-bot-api.deposit_enabled' => true]);
        $capabilities = \App\Services\Bot\BotGatewayCapabilities::forSource('telegram_gateway');
        $formatter = app(BotMessageFormatter::class);
        $menu = $formatter->formatCategories([
            'ok' => true,
            'data' => [['name' => 'Top Up Games', 'slug' => 'top-up-games']],
        ], 1, $capabilities);
        $keyboard = $formatter->defaultReplyKeyboard($capabilities);

        $menuCallbacks = collect($menu['buttons'])->flatten(1)->pluck('callback');
        $keyboardLabels = collect($keyboard['keyboard'])->flatten(1)->pluck('text');

        $this->assertTrue($menuCallbacks->contains('leaderboard'));
        $this->assertTrue($menuCallbacks->contains('deposit'));
        $this->assertTrue($keyboardLabels->contains('📜 Riwayat Order'));
        $this->assertTrue($keyboardLabels->contains('💰 Deposit'));
    }

    public function test_telegram_non_member_must_join_before_opening_menu(): void
    {
        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.id' => '@testchannel',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/testchannel',
        ]);
        Http::fake([
            'https://api.telegram.org/botdummy-token/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'left'],
            ]),
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => '/menu',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'getChatMember')) {
                return false;
            }

            return $request['chat_id'] === '@testchannel'
                && $request['user_id'] === 9876;
        });
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), 'sendMessage')) {
                return false;
            }

            $keyboard = $request['reply_markup']['inline_keyboard'];

            return str_contains($request['text'], 'Gabung Channel Terlebih Dahulu')
                && $keyboard[0][0]['url'] === 'https://t.me/testchannel'
                && $keyboard[0][1]['callback_data'] === 'menu';
        });
    }

    public function test_telegram_callback_checks_callback_user_membership(): void
    {
        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.id' => '@testchannel',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/testchannel',
        ]);
        Http::fake([
            'https://api.telegram.org/botdummy-token/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member'],
            ]),
            'https://api.telegram.org/botdummy-token/answerCallbackQuery' => Http::response(['ok' => true]),
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJsonTelegram([
            'callback_query' => [
                'id' => 'cb_member',
                'from' => ['id' => 9876],
                'message' => [
                    'chat' => ['id' => -100999999],
                    'message_id' => 111,
                ],
                'data' => 'menu',
            ],
        ]);

        $response->assertOk();
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'getChatMember')
            && $request['user_id'] === 9876);
    }

    public function test_telegram_adapter_handles_callback_query()
    {
        Http::fake([
            'https://api.telegram.org/botdummy-token/answerCallbackQuery' => Http::response(['ok' => true]),
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $response = $this->postJsonTelegram([
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

        $response = $this->postJsonTelegram([
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

            return str_contains($request['text'], '· 1/2')
                && count($keyboard) === 6
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

        $response = $this->postJsonTelegram([
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

        $response = $this->postJsonTelegram([
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

    public function test_fonnte_accepts_payload_without_message_id(): void
    {
        Cache::flush();

        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->andReturn(['success' => true]);
        });

        $this->postJsonFonnte([
            'sender' => '6281234567890',
            'message' => 'menu',
        ])->assertOk();
    }

    public function test_fonnte_accepts_alternate_message_id_keys(): void
    {
        $mock = \Mockery::mock(WhatsappNotificationService::class);
        $mock->shouldReceive('sendMessage')->once()->andReturn(['success' => true]);
        $this->app->instance(WhatsappNotificationService::class, $mock);

        $this->postJsonFonnte([
            'sender' => '6281234567890',
            'message' => 'menu',
            'message_id' => 'ALT-MESSAGE-ID',
        ])->assertOk();
    }

    public function test_fonnte_skips_telegram_membership_check(): void
    {
        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.id' => '@testchannel',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/testchannel',
        ]);
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true]),
        ]);

        $response = $this->postJsonFonnte([
            'sender' => '6281234567890',
            'message' => 'menu',
            'id' => 'MSG-SKIP',
        ]);

        $response->assertOk();
        Http::assertNotSent(fn ($request): bool => str_contains($request->url(), 'getChatMember'));
    }

    public function test_fonnte_status_without_order_id_lists_recent_orders_of_sender(): void
    {
        Cache::flush();
        Http::fake([
            'https://api.fonnte.com/send' => Http::response(['status' => true]),
        ]);

        $sender = '6281234567890';

        // Dua order LAMA yang sudah final (sukses) — tidak masuk
        // kriteria activeOrdersForSender, tapi harus tetap tampil di
        // ringkasan recent.
        foreach (['RECENT-OLD-1', 'RECENT-OLD-2'] as $index => $orderId) {
            $order = \App\Models\Pembelian::query()->create([
                'order_id' => $orderId,
                'username' => 'Anonim',
                'layanan' => 'Diamond Test ' . $index,
                'harga' => 10000,
                'profit' => 500,
                'user_id' => '12345',
                'zone' => '1234',
                'status' => 'Sukses',
                'traffic_source' => 'whatsapp_gateway',
            ]);
            \App\Models\Pembayaran::query()->create([
                'order_id' => $orderId,
                'no_pembayaran' => 'PAY-' . $orderId,
                'no_pembeli' => $sender,
                'status' => 'Lunas',
                'metode' => 'qris',
                'harga' => '10000',
            ]);
        }

        $sentMessages = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sentMessages): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $target, string $message) use (&$sentMessages): array {
                    $sentMessages[] = compact('target', 'message');

                    return ['success' => true];
                });
        });

        $response = $this->postJsonFonnte([
            'sender' => $sender,
            'message' => 'status',
            'id' => 'MSG-STATUS-RECENT',
        ]);

        $response->assertOk();

        $this->assertCount(1, $sentMessages);
        $text = $sentMessages[0]['message'];
        $this->assertStringContainsString('Pesanan Aktif', $text);
        $this->assertStringContainsString('RECENT-OLD-1', $text);
        $this->assertStringContainsString('RECENT-OLD-2', $text);
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

        $response = $this->postJsonFonnte([
            'sender' => '6281234567890',
            'message' => 'layanan mlbb',
            'id' => 'MSG123',
        ]);

        $response->assertOk()->assertJsonPath('status', true);
    }

    public function test_fonnte_numeric_menu_maps_selection_and_rejects_out_of_range_choice(): void
    {
        Cache::flush();

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
        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '100 DM',
            'harga_member' => 15000,
            'status' => 'available',
        ]);

        $sentMessages = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sentMessages): void {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturnUsing(function (string $target, string $message) use (&$sentMessages): array {
                    $sentMessages[] = compact('target', 'message');

                    return ['success' => true];
                });
        });

        $this->postJsonFonnte([
            'sender' => '+62 812-3456-7890',
            'message' => 'menu',
            'id' => 'MSG-NUMERIC-1',
        ])->assertOk();

        $this->assertStringContainsString('1. 🎮 Top Up Games', $sentMessages[0]['message']);
        $this->assertStringContainsString('🏆 Leaderboard — Ketik `leaderboard`', $sentMessages[0]['message']);
        $this->assertStringNotContainsString('Ketik nomor pilihan di atas.', $sentMessages[0]['message']);

        $stateKey = 'bot:numeric-menu:' . hash('sha256', 'whatsapp:6281234567890');
        $state = Cache::get($stateKey);
        $this->assertSame(1, $state['schema_version']);
        $this->assertSame('whatsapp_gateway', $state['source']);
        $this->assertSame('categories', $state['menu']);
        $this->assertSame('kategori top-up-games', $state['entries']['1']['command']);
        $this->assertSame('content', $state['entries']['1']['type']);

        $this->postJsonFonnte([
            'sender' => '6281234567890',
            'message' => '1',
            'id' => 'MSG-NUMERIC-2',
        ])->assertOk();

        $this->assertStringContainsString('🎮 *Pilih Game* · Top Up Games', $sentMessages[1]['message']);
        $this->assertStringContainsString('1. ⚔️ Mobile Legends', $sentMessages[1]['message']);
        $this->assertSame('products', Cache::get($stateKey)['menu']);
        $this->assertSame('layanan mlbb', Cache::get($stateKey)['entries']['1']['command']);
        $this->assertSame('menu', Cache::get($stateKey)['entries']['0']['command']);
        $this->assertStringContainsString('0. 🔙 Kembali', $sentMessages[1]['message']);

        $this->postJsonFonnte([
            'sender' => '6281234567890',
            'message' => '5',
            'id' => 'MSG-NUMERIC-3',
        ])->assertOk();

        $this->assertStringStartsWith('Pilihan tidak valid. Gunakan nomor yang tercantum pada menu aktif.', $sentMessages[2]['message']);
        $this->assertStringContainsString('🎮 *Pilih Game* · Top Up Games', $sentMessages[2]['message']);
        $this->assertStringNotContainsString('Perintah tidak dikenali', $sentMessages[2]['message']);
        $this->assertSame('products', Cache::get($stateKey)['menu']);
    }

    public function test_fonnte_numeric_menu_maps_pagination_on_the_active_page(): void
    {
        Cache::flush();

        foreach (range(1, 16) as $index) {
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

        $sentMessages = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sentMessages): void {
            $mock->shouldReceive('sendMessage')
                ->times(2)
                ->andReturnUsing(function (string $target, string $message) use (&$sentMessages): array {
                    $sentMessages[] = compact('target', 'message');

                    return ['success' => true];
                });
        });

        $this->postJsonFonnte([
            'sender' => '6281111111111',
            'message' => 'menu',
            'id' => 'MSG-PAGE-1',
        ])->assertOk();

        $stateKey = 'bot:numeric-menu:' . hash('sha256', 'whatsapp:6281111111111');
        $pageOneState = Cache::get($stateKey);
        $this->assertSame(1, $pageOneState['page']);
        $this->assertSame('menu page:2', $pageOneState['entries']['99']['command']);
        $this->assertStringContainsString('99. Next ➡️', $sentMessages[0]['message']);

        $this->postJsonFonnte([
            'sender' => '6281111111111',
            'message' => '99',
            'id' => 'MSG-PAGE-2',
        ])->assertOk();

        $pageTwoState = Cache::get($stateKey);
        $this->assertSame(2, $pageTwoState['page']);
        $this->assertSame('kategori top-up-16', $pageTwoState['entries']['1']['command']);
        $this->assertSame('menu page:1', $pageTwoState['entries']['98']['command']);
        $this->assertStringContainsString('· 2/2', $sentMessages[1]['message']);
        $this->assertStringContainsString('1. 🎮 Top Up 16', $sentMessages[1]['message']);
        $this->assertStringContainsString('98. ⬅️ Prev', $sentMessages[1]['message']);
    }

    public function test_fonnte_numeric_menu_rejects_corrupt_state_and_clears_it(): void
    {
        Cache::flush();
        $sender = '6286666666666';
        $stateKey = 'bot:numeric-menu:' . hash('sha256', 'whatsapp:' . $sender);
        Cache::put($stateKey, [
            'schema_version' => 999,
            'revision' => 'invalid',
            'source' => 'whatsapp_gateway',
            'entries' => [
                '1' => [
                    'type' => 'content',
                    'label' => 'Injected',
                    'command' => 'deposit 10000 BCA',
                ],
            ],
            'rendered_text' => 'Corrupt menu',
        ], now()->addMinutes(15));
        $sentMessage = null;
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sentMessage): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $target, string $message) use (&$sentMessage): array {
                    $sentMessage = $message;

                    return ['success' => true];
                });
        });

        $this->postJsonFonnte([
            'sender' => $sender,
            'message' => '1',
            'id' => 'MSG-CORRUPT-STATE',
        ])->assertOk();

        $this->assertSame(
            'Menu sudah kedaluwarsa. Ketik MENU untuk menampilkan pilihan terbaru.',
            $sentMessage,
        );
        $this->assertFalse(Cache::has($stateKey));
    }

    public function test_fonnte_numeric_input_preserves_waiting_checkout_precedence(): void
    {
        Cache::flush();
        $sender = '6285555555555';
        $externalUserId = 'whatsapp:' . $sender;
        $numericStateKey = 'bot:numeric-menu:' . hash('sha256', 'whatsapp:' . $sender);
        Cache::put($numericStateKey, [
            'schema_version' => 1,
            'revision' => 'AbCdEfGhIjKlMnOp',
            'source' => 'whatsapp_gateway',
            'menu' => 'categories',
            'entries' => [
                '1' => [
                    'type' => 'content',
                    'label' => 'Top Up',
                    'command' => 'menu',
                ],
            ],
            'parent_menu' => null,
            'page' => 1,
            'cursor' => null,
            'created_at' => now()->subMinute()->toIso8601String(),
            'expires_at' => now()->addMinutes(14)->toIso8601String(),
            'rendered_text' => 'Active menu',
        ], now()->addMinutes(15));
        Cache::put($this->checkoutStateKey($externalUserId, 'whatsapp_gateway'), [
            'step' => 'waiting_confirmation',
            'intent_token' => 'token',
        ], now()->addMinutes(15));
        $sentMessage = null;
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sentMessage): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $target, string $message) use (&$sentMessage): array {
                    $sentMessage = $message;

                    return ['success' => true];
                });
        });

        $this->postJsonFonnte([
            'sender' => $sender,
            'message' => '1',
            'id' => 'MSG-CHECKOUT-PRECEDENCE',
        ])->assertOk();

        $this->assertStringContainsString('Perintah tidak dikenali', (string) $sentMessage);
        $this->assertStringNotContainsString('Menu Utama', (string) $sentMessage);
    }

    public function test_fonnte_numeric_input_without_state_returns_expired_menu_recovery(): void
    {
        Cache::flush();
        $sentMessage = null;
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sentMessage): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $_target, string $message) use (&$sentMessage): array {
                    $sentMessage = $message;

                    return ['success' => true];
                });
        });

        $this->postJsonFonnte([
            'sender' => '6289999999999',
            'message' => '1',
            'id' => 'MSG-NO-STATE',
        ])->assertOk();

        $this->assertSame(
            'Menu sudah kedaluwarsa. Ketik MENU untuk menampilkan pilihan terbaru.',
            $sentMessage,
        );
    }

    public function test_fonnte_long_command_still_works_and_telegram_does_not_create_numeric_state(): void
    {
        Cache::flush();

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
        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'status' => 'available',
        ]);

        $sentMessage = null;
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sentMessage): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $_target, string $message) use (&$sentMessage): array {
                    $sentMessage = $message;

                    return ['success' => true];
                });
        });

        $this->postJsonFonnte([
            'sender' => '6287777777777',
            'message' => 'kategori top-up-games',
            'id' => 'MSG-LONG-COMMAND',
        ])->assertOk();

        $this->assertStringContainsString('🎮 *Pilih Game* · Top Up Games', (string) $sentMessage);

        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);
        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'menu',
                'message_id' => 111,
            ],
        ])->assertOk();

        $telegramStateKey = 'bot:numeric-menu:' . hash('sha256', 'whatsapp:9876');
        $this->assertFalse(Cache::has($telegramStateKey));
    }

    public function test_telegram_leaderboard_command_renders_rankings(): void
    {
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->mock(\App\Services\LeaderboardService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('rankings')->once()->andReturn([
                'today' => [['username' => 'Ali**', 'total_harga' => 25000]],
                'week' => [['username' => 'Budi**', 'total_harga' => 50000]],
                'month' => [],
            ]);
        });

        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'leaderboard',
                'message_id' => 111,
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'sendMessage')
                && str_contains($request['text'], '🏆 *Leaderboard*')
                && str_contains($request['text'], 'Ali\\*\\*')
                && str_contains($request['text'], 'Rp 25.000')
                && str_contains($request['text'], 'Bulan Ini')
                && $request['reply_markup']['inline_keyboard'][0][0]['callback_data'] === 'menu';
        });
    }

    public function test_fonnte_leaderboard_command_renders_rankings_without_numeric_menu_state(): void
    {
        Cache::flush();
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->once()
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(\App\Services\LeaderboardService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('rankings')->once()->andReturn([
                'today' => [['username' => 'Ali**', 'total_harga' => 25000]],
                'week' => [],
                'month' => [],
            ]);
        });

        $this->postJsonFonnte([
            'sender' => '6281234567890',
            'message' => '🏆 Leaderboard',
            'id' => 'MSG-LEADERBOARD-1',
        ])->assertOk();

        $this->assertStringContainsString('🏆 *Leaderboard*', $sent[0]['message']);
        $this->assertStringContainsString('Ali\\*\\*', $sent[0]['message']);
        $this->assertStringContainsString('Rp 25.000', $sent[0]['message']);
        $this->assertFalse(Cache::has('bot:numeric-menu:' . hash('sha256', 'whatsapp:6281234567890')));
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

        $this->assertSame("✅ *Pembayaran Berhasil*\n\nPesanan kamu sudah diterima dan sedang diproses.\n\nKami akan mengirimkan notifikasi setelah top up selesai.\n\n💎 Mobile \\*Legends\\*\n👤 Player\n\n🧾 `INV_[123]`", $response['text']);
        $this->assertSame('🔙 Kembali ke Menu', $response['buttons'][0][0]['text']);
    }

    public function test_telegram_status_renders_success_order_with_top_up_berhasil_title(): void
    {
        config(['app.name' => 'Z_Vault *Store*']);

        $response = app(BotMessageFormatter::class)->formatStatus([
            'ok' => true,
            'data' => [
                'order_id' => 'INV-456',
                'product' => 'Mobile Legends',
                'nickname' => 'Player',
                'amount' => 10000,
                'status' => 'Sukses',
                'payment' => ['status' => 'Lunas'],
                'sn' => 'SN-123456789',
            ],
        ]);

        $this->assertSame("✅ *Top Up Berhasil!*\n\nPesanan sudah berhasil diproses dan masuk ke akun kamu 🎉\n\n💎 Mobile Legends\n👤 Player\n🔑 SN: `SN-123456789`\n\n🧾 `INV-456`\n\nTerima kasih sudah berbelanja di *Z\\_Vault \\*Store\\**.\nButuh produk lain? Cek katalog kami kapan saja.", $response['text']);
        $this->assertSame('🔙 Kembali ke Menu', $response['buttons'][0][0]['text']);
    }

    public function test_telegram_status_renders_unpaid_payment_details(): void
    {
        config([
            'app.name' => 'Z_Vault *Store*',
            'app.timezone' => 'Asia/Jakarta',
        ]);

        $response = app(BotMessageFormatter::class)->formatStatus([
            'ok' => true,
            'data' => [
                'order_id' => 'INV_[123]',
                'product' => 'Mobile *Legends*',
                'nickname' => 'Player',
                'amount' => 10000,
                'status' => 'Pending',
                'payment' => [
                    'status' => 'Belum Lunas',
                    'amount' => 12500,
                    'method' => 'BCA_VA',
                    'payment_code' => '12345_678',
                    'expires_at' => '2026-07-30T06:00:00+00:00',
                ],
                'sn' => null,
            ],
        ]);

        $this->assertSame("⏳ *Menunggu Pembayaran*\n\n💎 Mobile \\*Legends\\*\n💰 *Rp 12.500*\n🧾 `INV_[123]`\n\n💳 Metode: *BCA\\_VA*\nKetik `status` untuk cek pembayaran.", $response['text']);
    }

    public function test_telegram_status_hides_qris_payload_for_unpaid_payment(): void
    {
        $qrisPayload = '00020101021226610014COM.GO-JEK.WWW01189360091430274901050210G1234567890303UMI51440014ID.CO.QRIS.WWW0215ID10200423000030303UMI5204541153033605802ID5910Test Store6007Jakarta6105123456304ABCD';

        $response = app(BotMessageFormatter::class)->formatStatus([
            'ok' => true,
            'data' => [
                'order_id' => 'INV-123',
                'product' => 'Mobile Legends',
                'amount' => 10000,
                'payment' => [
                    'status' => 'Belum Lunas',
                    'method' => 'QRIS',
                    'payment_code' => $qrisPayload,
                ],
            ],
        ]);

        $this->assertStringContainsString('💳 Metode: *QRIS*', $response['text']);
        $this->assertStringNotContainsString($qrisPayload, $response['text']);
    }

    public function test_telegram_status_renders_expired_payment_message(): void
    {
        config(['app.name' => 'Z_Vault *Store*']);

        $response = app(BotMessageFormatter::class)->formatStatus([
            'ok' => true,
            'data' => [
                'order_id' => 'INV_[123]',
                'product' => 'Mobile *Legends*',
                'payment' => ['status' => 'Expired'],
            ],
        ]);

        $this->assertStringContainsString('❌ *Pembayaran Kadaluarsa*', $response['text']);
        $this->assertStringContainsString('💎 Mobile \\*Legends\\*', $response['text']);
        $this->assertStringContainsString('🧾 `INV_[123]`', $response['text']);
        $this->assertSame('🔙 Kembali ke Menu', $response['buttons'][0][0]['text']);
        $this->assertStringNotContainsString('*Status Pesanan*', $response['text']);
    }

    public function test_telegram_status_treats_kadaluarsa_as_expired_payment(): void
    {
        $response = app(BotMessageFormatter::class)->formatStatus([
            'ok' => true,
            'data' => [
                'order_id' => 'INV-123',
                'product' => 'Mobile Legends',
                'payment' => ['status' => '  KADALUARSA  '],
            ],
        ]);

        $this->assertStringContainsString('❌ *Pembayaran Kadaluarsa*', $response['text']);
        $this->assertStringContainsString('🧾 `INV-123`', $response['text']);
        $this->assertStringContainsString('💎 Mobile Legends', $response['text']);
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

        $this->assertStringContainsString('🎮 *Masukkan Game\\_ID*', $quote['text']);
        $this->assertStringContainsString('`UID <Server_ID>`', $quote['text']);
        $this->assertStringContainsString('🎮 *Masukkan Game\\_ID*', $retry['text']);
        $this->assertStringContainsString('`UID <Server_ID>`', $retry['text']);
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
            $mock->shouldReceive('quote')->twice()->andReturn($this->fakePriceQuote(
                serviceId: $service->id,
                categoryCode: 'mobile-legends',
                requiresZoneId: false,
            ));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $handler->handle('harga', [(string) $service->id, 'QRIS'], $context);
        $invalid = $handler->handle('12345', [], $context);
        $success = $handler->handle('12345', ['6789'], $context);

        $this->assertStringContainsString('UID <Server ID>', $invalid['text']);
        $this->assertSame('🧾 *Cek Pesanan*', strtok($success['text'], "\n"));
        $this->assertSame(
            'waiting_confirmation',
            Cache::get($this->checkoutStateKey('telegram:9876'))['step'],
        );
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
            $mock->shouldReceive('quote')->twice()->andReturn($this->fakePriceQuote(
                serviceId: $service->id,
                categoryCode: 'mobile-legends',
                requiresZoneId: true,
                customInputs: app(\App\Support\CustomInputDefaults::class)->inputSpecification(
                    Kategori::query()->findOrFail($service->kategori_id),
                ),
            ));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $quote = $handler->handle('harga', [(string) $service->id, 'QRIS'], $context);
        $success = $handler->handle('12345', ['asia', 'tenggara'], $context);

        $this->assertStringContainsString('Asia Tenggara: `asia tenggara`', $quote['text']);
        $this->assertSame('🧾 *Cek Pesanan*', strtok($success['text'], "\n"));
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
            $mock->shouldReceive('quote')->twice()->andReturn($this->fakePriceQuote(
                serviceId: $service->id,
                categoryCode: 'free-fire',
                requiresZoneId: true,
            ));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $handler->handle('harga', [(string) $service->id, 'QRIS'], $context);
        $success = $handler->handle('12345', [], $context);

        $this->assertSame('🧾 *Cek Pesanan*', strtok($success['text'], "\n"));
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
            $mock->shouldReceive('quote')->once()->withArgs(
                fn (array $payload, $user): bool => $user === null
                    && $payload['service'] === '123'
                    && $payload['payment_method'] === 'QRIS'
                    && $payload['uid'] === '12345'
                    && $payload['zone'] === '6789'
                    && $payload['email'] === '9876@telegram.user',
            )->andReturn($this->fakePriceQuote(requiresZoneId: true));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withArgs(function (array $payload, $user, string $source, array $invoiceContext): bool {
                    return $user === null
                        && $source === 'telegram_gateway'
                        && $payload['service'] === '123'
                        && $payload['payment_method'] === 'QRIS'
                        && $payload['uid'] === '12345'
                        && $payload['zone'] === '6789'
                        && $payload['email'] === '9876@telegram.user'
                        && filled($payload['intent_id'] ?? null)
                        && ($invoiceContext['intent_id'] ?? null) === $payload['intent_id'];
                })
                ->andReturn($this->fakeInvoiceResponse());
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $quote = $handler->handle('harga', ['123', 'QRIS'], $context);
        $confirmation = $handler->handle('12345', ['6789'], $context);
        $state = Cache::get($this->checkoutStateKey('telegram:9876'));

        $this->assertStringContainsString('🎮 *Masukkan User ID*', $quote['text']);
        $this->assertSame('🧾 *Cek Pesanan*', strtok($confirmation['text'], "\n"));
        $this->assertSame('waiting_confirmation', $state['step']);
        $this->assertStringNotContainsString('Batal Checkout', $confirmation['text']);

        $confirmationButtons = collect($confirmation['buttons'] ?? [])
            ->flatMap(fn ($row): array => is_array($row) ? $row : [$row])
            ->pluck('text')
            ->filter()
            ->all();
        $this->assertSame(['✅ Konfirmasi', '❌ Batal'], $confirmationButtons);

        $invoiceResponse = $handler->handle(
            'konfirmasi',
            [$state['intent_token']],
            $context + ['message_id' => 'telegram:12345:112'],
        );

        $this->assertSame('⏳ *Menunggu Pembayaran*', strtok($invoiceResponse['text'], "\n"));
        $this->assertNull(Cache::get($this->checkoutStateKey('telegram:9876')));
    }

    public function test_telegram_checkout_zero_at_uid_input_returns_to_payment_selection()
    {
        Cache::flush();
        $this->createConversationService('mobile-legends', true, 123);
        Method::query()->create([
            'name' => 'QRIS',
            'code' => 'QRIS',
            'payment' => 'duitku',
            'tipe' => 'qris',
            'images' => '/images/qris.png',
            'keterangan' => 'Bayar dengan QRIS',
            'statuspayment' => 1,
            'fee_percent' => 0,
            'fix_fee' => 0,
        ]);
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];

        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')
                ->once()
                ->withArgs(fn (array $payload, $user) => $user === null)
                ->andReturn($this->fakePriceQuote(requiresZoneId: true));
        });
        $invoice = $this->mock(GatewayInvoiceService::class);
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $handler->handle('harga', ['123', 'QRIS'], $context);
        $state = Cache::get($this->checkoutStateKey('telegram:9876'));
        $this->assertSame('waiting_game_id', $state['step']);

        $backToPayment = $handler->handle('0', [], $context);
        $this->assertStringContainsString('Pilih Pembayaran', $backToPayment['text']);
        $this->assertStringContainsString('harga 123 QRIS', json_encode($backToPayment['buttons']));
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

    public function test_telegram_checkout_blocks_invalid_game_account_before_creating_intent(): void
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
            // Quote hanya dipanggil saat state harga, bukan untuk UID.
            $mock->shouldReceive('quote')->once()->andReturn($this->fakePriceQuote(requiresZoneId: true));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $checkId = $this->mock(GatewayCheckIdService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('check')->once()->withArgs(function (array $payload): bool {
                return ($payload['category_code'] ?? '') === 'mobile-legends'
                    && (int) ($payload['service_id'] ?? 0) === 123
                    && ($payload['uid'] ?? '') === '12345'
                    && ($payload['zone'] ?? null) === '6789';
            })->andReturn([
                'ok' => false,
                'error_code' => 'CHECK_ID_FAILED',
                'message' => 'User ID tidak ditemukan.',
                'data' => ['skip_check' => false, 'valid' => false, 'uid' => '12345', 'nickname' => null],
            ]);
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice, $checkId);

        $handler->handle('harga', ['123', 'QRIS'], $context);
        $blocked = $handler->handle('12345', ['6789'], $context);

        $this->assertStringContainsString('User ID tidak ditemukan', $blocked['text']);
        $this->assertDatabaseCount('bot_checkout_intents', 0);
        // State tetap waiting_game_id agar user bisa perbaiki UID.
        $state = Cache::get($this->checkoutStateKey('telegram:9876'));
        $this->assertSame('waiting_game_id', $state['step']);
    }

    public function test_telegram_membership_denial_blocks_input_and_clears_checkout_state(): void
    {
        Cache::flush();
        config([
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.id' => '@testchannel',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/testchannel',
        ]);
        Http::fake([
            'https://api.telegram.org/botdummy-token/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'left'],
            ]),
        ]);

        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'telegram_user_id' => 9876,
            'message_id' => 'telegram:12345:111',
        ];
        Cache::put($this->checkoutStateKey('telegram:9876'), [
            'step' => 'waiting_game_id',
            'service_id' => 123,
            'payment_method' => 'QRIS',
            'category_code' => 'mobile-legends',
        ], now()->addMinutes(15));

        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('quote');
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $response = $handler->handle('12345', ['6789'], $context);

        $this->assertStringContainsString('Gabung Channel Terlebih Dahulu', $response['text']);
        $this->assertNull(Cache::get($this->checkoutStateKey('telegram:9876')));
    }

    public function test_telegram_invoice_requires_confirmation_and_freezes_synthetic_email_contact()
    {
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });

        $response = $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ],
        ]);

        $response->assertOk();
        $intent = \App\Models\BotCheckoutIntent::query()->sole();

        $this->assertSame(
            \App\Models\BotCheckoutIntent::STATUS_AWAITING_CONFIRMATION,
            $intent->status,
        );
        $this->assertSame(
            '9876@telegram.user',
            $intent->payload['email'],
        );
        Http::assertSent(fn ($request): bool => str_contains(
            $request->url(),
            'sendMessage',
        ) && str_contains($request['text'], '🧾 *Cek Pesanan*'));
    }

    public function test_duplicate_invoice_origin_replays_awaiting_confirmation_intent(): void
    {
        Cache::flush();
        $context = [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'telegram:12345:111',
            'email' => '9876@telegram.user',
        ];
        $pricing = $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')
                ->twice()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true));
        });
        $invoice = $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('createInvoice');
        });
        $handler = $this->makeBotCommandHandler($pricing, $invoice);

        $first = $handler->handle('invoice', ['1', 'QRIS', 'user123', 'zone123'], $context);
        $second = $handler->handle('invoice', ['1', 'QRIS', 'user123', 'zone123'], $context);
        $intent = \App\Models\BotCheckoutIntent::query()->sole();
        $state = Cache::get($this->checkoutStateKey('telegram:9876'));

        $this->assertSame($first['text'], $second['text']);
        $this->assertSame(
            \App\Models\BotCheckoutIntent::STATUS_AWAITING_CONFIRMATION,
            $intent->status,
        );
        $this->assertSame($intent->intent_id, $state['intent_id']);
        $this->assertDatabaseCount('bot_checkout_intents', 1);
    }

    public function test_telegram_invoice_sends_qris_image_when_provider_returns_qris_url()
    {
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
            'https://api.telegram.org/botdummy-token/sendPhoto'   => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse('https://provider.example/qris/INV-1.png'));
        });

        // Step 1: invoice command → confirmation prompt, no provider call yet
        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ],
        ])->assertOk();

        // Step 2: confirm with token from cache → provider call + sendPhoto
        $token = $this->telegramCheckoutToken('telegram:default:9876');
        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'konfirmasi ' . $token,
                'message_id' => 112,
            ],
        ])->assertOk();

        Http::assertSent(function ($request): bool {
            $keyboard = $request['reply_markup']['inline_keyboard'];

            return str_contains($request->url(), 'sendPhoto')
                && $request['photo'] === 'https://provider.example/qris/INV-1.png'
                && str_contains($request['caption'], '⏳ *Menunggu Pembayaran*')
                && str_contains($request['caption'], '🧾 `INV-1`')
                && str_contains($request['caption'], '💎 Mobile Legends (Top Up Games)')
                && str_contains($request['caption'], '💰 *Rp 10.000*')
                && str_contains($request['caption'], 'Ketik `status` untuk cek pembayaran.')
                && ! str_contains($request['caption'], 'Kode Bayar / VA')
                && ! str_contains($request['caption'], 'Link Pembayaran:')
                && $keyboard[0][0]['text'] === '🔗 Buka Halaman Invoice'
                && $keyboard[0][0]['url'] === 'https://pay.example/inv-1'
                && $keyboard[1][0]['text'] === '🔎 Cek Status Pembayaran'
                && isset($keyboard[1][0]['callback_data']);
        });
    }

    public function test_telegram_invoice_sends_qris_image_when_payment_code_is_direct_image_url()
    {
        $qrisUrl = 'https://assets-b3xk0.b-cdn.net/2026/07/TP260729IGRQ307771.png';
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        Http::fake([
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
            'https://api.telegram.org/botdummy-token/sendPhoto'   => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($qrisUrl): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: $qrisUrl));
        });

        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ],
        ])->assertOk();

        $token = $this->telegramCheckoutToken('telegram:default:9876');
        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'konfirmasi ' . $token,
                'message_id' => 112,
            ],
        ])->assertOk();

        Http::assertSent(function ($request) use ($qrisUrl): bool {
            $keyboard = $request['reply_markup']['inline_keyboard'];

            return str_contains($request->url(), 'sendPhoto')
                && $request['photo'] === $qrisUrl
                && ! str_contains($request['caption'], 'Kode Bayar / VA')
                && $keyboard[0][0]['url'] === 'https://pay.example/inv-1'
                && isset($keyboard[1][0]['callback_data']);
        });
    }

    public function test_invoice_formatter_uses_normalized_gateway_qr_image_url(): void
    {
        $cases = [
            ['provider' => 'tripay', 'url' => 'https://tripay.co.id/qr/REF-1'],
            ['provider' => 'duitku', 'url' => 'https://cdn.duitku.test/qr/REF-2.png'],
            ['provider' => 'tokopay', 'url' => 'https://cdn.tokopay.test/qr/REF-3.png'],
        ];

        foreach ($cases as $case) {
            $response = app(BotMessageFormatter::class)->formatInvoice([
                'ok' => true,
                'data' => [
                    'order_id' => 'INV-' . $case['provider'],
                    'invoice_url' => 'https://app.example/invoice',
                    'payment_url' => 'https://checkout.example/pay',
                    'payment' => [
                        'payment_code' => 'PAY-' . $case['provider'],
                        'qr_image_url' => $case['url'],
                        'amount' => 10000,
                    ],
                ],
            ]);

            $this->assertSame($case['url'], $response['photo_url']);
            $this->assertSame('https://app.example/invoice', $response['buttons'][0][0]['url']);
        }
    }

    public function test_invoice_formatter_uses_extensionless_tripay_qr_url_directly(): void
    {
        $tripayUrl = 'https://tripay.co.id/qr/DEV-EM260810131805T6WB2A';

        $response = app(BotMessageFormatter::class)->formatInvoice([
            'ok' => true,
            'data' => [
                'order_id' => 'EM260810131805T6WB2A',
                'payment_url' => $tripayUrl,
                'payment' => [
                    'payment_code' => $tripayUrl,
                    'amount' => 10000,
                ],
            ],
        ]);

        $this->assertSame($tripayUrl, $response['photo_url']);
        $this->assertStringNotContainsString('api.qrserver.com', $response['photo_url']);
    }

    public function test_invoice_formatter_checks_nested_gateway_image_fields(): void
    {
        $cases = [
            ['payment' => ['pay_url' => 'https://tripay.co.id/payment/DEV-1']],
            ['data' => ['pay_url' => 'https://tripay.co.id/qr/DEV-2']],
            ['paymentUrl' => 'https://duitku.example/qr/DEV-3.png'],
            ['qr_link' => 'https://tokopay.example/qr/DEV-4.png'],
        ];

        foreach ($cases as $fields) {
            $response = app(BotMessageFormatter::class)->formatInvoice([
                'ok' => true,
                'data' => array_merge([
                    'order_id' => 'INV-1',
                    'payment' => ['payment_code' => 'QRIS-1', 'amount' => 10000],
                ], $fields),
            ]);

            $expected = data_get($fields, 'payment.pay_url')
                ?? data_get($fields, 'data.pay_url')
                ?? data_get($fields, 'paymentUrl')
                ?? data_get($fields, 'qr_link');

            $this->assertSame($expected, $response['photo_url']);
        }
    }

    public function test_invoice_formatter_does_not_treat_untrusted_extensionless_url_as_image(): void
    {
        $checkoutUrl = 'https://evil-tripay.co.id/qr/DEV-1';

        $response = app(BotMessageFormatter::class)->formatInvoice([
            'ok' => true,
            'data' => [
                'order_id' => 'INV-1',
                'payment_url' => $checkoutUrl,
                'payment' => ['payment_code' => 'QRIS-1', 'amount' => 10000],
            ],
        ]);

        $this->assertArrayNotHasKey('photo_url', $response);
        $this->assertSame($checkoutUrl, $response['buttons'][0][0]['url']);
    }

    public function test_fonnte_invoice_sends_direct_qris_url_as_media(): void
    {
        $tripayUrl = 'https://tripay.co.id/qr/DEV-FONNTE-1';
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($tripayUrl): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: $tripayUrl));
        });

        // Step 1: invoice → confirmation prompt
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'invoice 1 QRIS user123 zone123',
            'id'      => 'MSG-QRIS-1',
        ])->assertOk();

        // Step 2: confirm → invoice + media
        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG-QRIS-1-CONFIRM',
        ])->assertOk();

        $this->assertNull($sent[1]['url']);
        $this->assertSame($tripayUrl, $sent[2]['url']);
        $this->assertSame('', $sent[2]['message']);
    }

    public function test_fonnte_invoice_does_not_include_invoice_website_link_for_qris_payment(): void
    {
        $tripayUrl = 'https://tripay.co.id/qr/DEV-FONNTE-LINK-1';
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($tripayUrl): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: $tripayUrl));
        });

        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'invoice 1 QRIS user123 zone123',
            'id'      => 'MSG-QRIS-LINK-1',
        ])->assertOk();

        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG-QRIS-LINK-1-CONFIRM',
        ])->assertOk();

        $this->assertStringNotContainsString('https://pay.example/inv-1', $sent[1]['message']);
        $this->assertSame($tripayUrl, $sent[2]['url']);
    }

    public function test_fonnte_invoice_sends_generated_qris_media_from_raw_qr_payload(): void
    {
        $rawQris = '00020101021226610014COM.GO-JEK.WWW01189360091430274901050210G1234567890303UMI51440014ID.CO.QRIS.WWW0215ID10200423000030303UMI5204541153033605802ID5910Test Store6007Jakarta6105123456304ABCD';
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($rawQris): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: 'QRIS', qrPayload: $rawQris));
        });

        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'invoice 1 QRIS user123 zone123',
            'id'      => 'MSG-QRIS-RAW-1',
        ])->assertOk();

        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG-QRIS-RAW-1-CONFIRM',
        ])->assertOk();

        $this->assertStringNotContainsString($rawQris, $sent[1]['message']);
        $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $sent[2]['url']);
        $this->assertStringNotContainsString('https://pay.example/inv-1', $sent[1]['message']);
    }

    public function test_fonnte_tokopay_qr_link_is_sent_as_media(): void
    {
        $qrLink = 'https://assets.tokopay.id/2023/06/TP230608ZYOF006758.png';
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($qrLink): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(
                    paymentCode: 'QRIS',
                    qrImageUrl: $qrLink,
                ));
        });

        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'invoice 1 QRIS user123 zone123',
            'id'      => 'MSG-TOKOPAY-QR-LINK',
        ])->assertOk();

        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG-TOKOPAY-QR-LINK-CONFIRM',
        ])->assertOk();

        $this->assertCount(3, $sent);
        $this->assertNull($sent[1]['url']);
        $this->assertSame($qrLink, $sent[2]['url']);
        $this->assertSame('', $sent[2]['message']);
        $this->assertStringNotContainsString($qrLink, $sent[1]['message']);
    }

    public function test_fonnte_tokopay_qr_string_is_generated_as_media(): void
    {
        $qrString = '00020101021226670016COM.NOBUBANK.WWW01189360050300000892760214050400006914590303UME51440014ID.CO.QRIS.WWW0215ID20232586149990303UME5204549953033605405250005802ID5908TOKO PAY6009Pekanbaru61052811162550114060800801516540618TP230608ZYOF0067580703A010804POSP6304B90A';
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->times(3)
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($qrString): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(
                    paymentCode: 'QRIS',
                    qrPayload: $qrString,
                ));
        });

        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'invoice 1 QRIS user123 zone123',
            'id'      => 'MSG-TOKOPAY-QR-STRING',
        ])->assertOk();

        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG-TOKOPAY-QR-STRING-CONFIRM',
        ])->assertOk();

        $this->assertCount(3, $sent);
        $this->assertStringStartsWith('https://api.qrserver.com/v1/create-qr-code/', $sent[2]['url']);
        $this->assertStringNotContainsString($qrString, $sent[1]['message']);
    }

    public function test_fonnte_tokopay_va_keeps_number_without_qris_media(): void
    {
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->times(2)
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: '8578330274425713'));
        });

        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'invoice 1 BCAVA user123 zone123',
            'id'      => 'MSG-TOKOPAY-VA',
        ])->assertOk();

        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG-TOKOPAY-VA-CONFIRM',
        ])->assertOk();

        $this->assertCount(2, $sent);
        $this->assertStringContainsString('Kode Bayar / VA: `8578330274425713`', $sent[1]['message']);
        $this->assertNull($sent[1]['url']);
    }

    public function test_fonnte_invoice_keeps_va_code_without_qris_media(): void
    {
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        $sent = [];
        $this->mock(WhatsappNotificationService::class, function (MockInterface $mock) use (&$sent): void {
            $mock->shouldReceive('sendMessage')
                ->times(2)
                ->andReturnUsing(function (string $target, string $message, ?string $url = null) use (&$sent): array {
                    $sent[] = compact('target', 'message', 'url');

                    return ['success' => true];
                });
        });
        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: '1234567890'));
        });

        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'invoice 1 BCAVA user123 zone123',
            'id'      => 'MSG-VA-1',
        ])->assertOk();

        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '6281234567890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG-VA-1-CONFIRM',
        ])->assertOk();

        $this->assertStringContainsString('Kode Bayar / VA: `1234567890`', $sent[1]['message']);
        $this->assertNull($sent[1]['url']);
    }

    public function test_telegram_invoice_hides_raw_qris_payload_and_sends_generated_qr_photo()
    {
        $rawQris = '00020101021226610014COM.GO-JEK.WWW01189360091430274901050210G1234567890303UMI51440014ID.CO.QRIS.WWW0215ID10200423000030303UMI5204541153033605802ID5910Test Store6007Jakarta6105123456304ABCD';
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
        Http::fake([
            'https://api.qrserver.com/v1/create-qr-code/*'       => Http::response('image', 200),
            'https://api.telegram.org/botdummy-token/sendMessage' => Http::response(['ok' => true]),
            'https://api.telegram.org/botdummy-token/sendPhoto'   => Http::response(['ok' => true]),
        ]);

        $this->mock(GatewayInvoiceService::class, function (MockInterface $mock) use ($rawQris): void {
            $mock->shouldReceive('createInvoice')
                ->once()
                ->withAnyArgs()
                ->andReturn($this->fakeInvoiceResponse(paymentCode: $rawQris));
        });

        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'invoice 1 QRIS user123 zone123',
                'message_id' => 111,
            ],
        ])->assertOk();

        $token = $this->telegramCheckoutToken('telegram:default:9876');
        $this->postJsonTelegram([
            'message' => [
                'chat' => ['id' => 12345],
                'from' => ['id' => 9876],
                'text' => 'konfirmasi ' . $token,
                'message_id' => 112,
            ],
        ])->assertOk();

        Http::assertSent(function ($request) use ($rawQris): bool {
            return str_contains($request->url(), 'sendPhoto')
                && str_starts_with($request['photo'], 'https://api.qrserver.com/v1/create-qr-code/')
                && ! str_contains($request['caption'], $rawQris)
                && ! str_contains($request['caption'], 'Kode Bayar / VA');
        });
    }

    public function test_fonnte_invoice_uses_sender_as_whatsapp_contact(): void
    {
        $this->setUpInvoiceService(serviceId: 1);
        $this->mock(GatewayPricingService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('quote')->once()->withAnyArgs()
                ->andReturn($this->fakePriceQuote(requiresZoneId: true, serviceId: 1));
        });
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

        // Step 1: invoice → confirmation (normalized number stored in intent)
        $this->postJsonFonnte([
            'sender'  => '+62 812-3456-7890',
            'message' => 'invoice 1 QRIS user123 zone123',
            'id'      => 'MSG123',
        ])->assertOk();

        // Step 2: confirm → createInvoice with whatsapp contact from frozen intent
        $token = $this->fonnteCheckoutToken('6281234567890');
        $this->postJsonFonnte([
            'sender'  => '+62 812-3456-7890',
            'message' => 'KONFIRMASI ' . $token,
            'id'      => 'MSG123-CONFIRM',
        ])->assertOk();
    }

    /**
     * Create a Layanan + Kategori fixture needed for invoice-command tests.
     */
    private function setUpInvoiceService(int $serviceId = 1): Layanan
    {
        $category = Kategori::factory()->create([
            'kode'      => 'mlbb',
            'server_id' => true,
            'status'    => 'active',
        ]);

        return Layanan::factory()->create([
            'id'          => $serviceId,
            'kategori_id' => $category->id,
            'status'      => 'available',
        ]);
    }

    /**
     * Return the intent action token stored in the checkout-state cache for a
     * Telegram user after the first invoice command. Extracts from the scoped
     * cache key so tests do not need to know the internal key format.
     */
    private function telegramCheckoutToken(string $externalUserId): string
    {
        $state = Cache::get($this->checkoutStateKey($externalUserId, 'telegram_gateway'));

        return (string) ($state['intent_token'] ?? '');
    }

    /**
     * Return the intent action token stored in the checkout-state cache for a
     * Fonnte/WhatsApp sender after the first invoice command.
     */
    private function fonnteCheckoutToken(string $normalizedNumber): string
    {
        $state = Cache::get($this->checkoutStateKey(
            'whatsapp:' . $normalizedNumber,
            'whatsapp_gateway',
        ));

        return (string) ($state['intent_token'] ?? '');
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

    private function makeBotCommandHandler(
        GatewayPricingService $pricing,
        GatewayInvoiceService $invoice,
        ?GatewayCheckIdService $checkId = null,
    ): BotCommandHandler
    {
        $checkId ??= $this->mock(GatewayCheckIdService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('check')->andReturn([
                'ok' => true,
                'data' => ['skip_check' => false, 'valid' => true, 'nickname' => 'QA Player'],
            ]);
        });

        return new BotCommandHandler(
            app(GatewayCatalogService::class),
            app(PaymentMethodCatalogService::class),
            $pricing,
            $checkId,
            $invoice,
            app(BotMessageFormatter::class),
            app(TelegramChannelMembershipService::class),
            app(\App\Services\LeaderboardService::class),
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

    private function checkoutStateKey(
        string $externalUserId,
        string $source = 'telegram_gateway',
    ): string {
        return 'bot:checkout-state:' . hash(
            'sha256',
            $source . '|' . $externalUserId,
        );
    }

    private function fakeInvoiceResponse(
        ?string $qrisUrl = null,
        string $paymentCode = 'QRIS-1',
        ?string $qrPayload = null,
        ?string $qrImageUrl = null,
    ): array {
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
                'payment' => array_filter([
                    'payment_code' => $paymentCode,
                    'qr_image_url' => $qrImageUrl,
                    'qr_payload' => $qrPayload,
                    'amount' => 10000,
                ]),
            ]),
        ];
    }
}
