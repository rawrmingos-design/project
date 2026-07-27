<?php

namespace Tests\Feature\Bot;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
}
