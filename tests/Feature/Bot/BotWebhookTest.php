<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Gateway\GatewayInvoiceService;
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

    private function fakeInvoiceResponse(): array
    {
        return [
            'ok' => true,
            'message' => 'Invoice berhasil dibuat.',
            'data' => [
                'order_id' => 'INV-1',
                'payment_url' => 'https://pay.example/inv-1',
                'payment' => [
                    'payment_code' => 'QRIS-1',
                    'amount' => 10000,
                ],
            ],
        ];
    }
}
