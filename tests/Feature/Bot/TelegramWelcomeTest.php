<?php

namespace Tests\Feature\Bot;

use App\Models\InboundSourcePolicy;
use App\Models\SettingWeb;
use App\Services\Bot\Adapters\TelegramAdapter;
use App\Services\Bot\TelegramWelcomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Sambutan otomatis member baru di grup Telegram.
 *
 * Fokus: perilaku yang MUDAH SALAH — menyapa bot sendiri, mengirim saat
 * saklar mati, dan menyapa orang yang sebenarnya bukan member baru.
 */
class TelegramWelcomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram-bot-api.token' => 'TEST-TG-TOKEN',
            'services.telegram-bot-api.webhook_secret' => 'secret',
            'services.telegram-bot-api.telegram_welcome_enabled' => false,
            'services.telegram-bot-api.telegram_welcome_template' => null,
            'services.telegram-bot-api.telegram_welcome_thread_id' => null,
        ]);

        // Baris setting_webs WAJIB ada: AppServiceProvider membangun ulang
        // config dari DB pada tiap request, sehingga config() di atas bisa
        // ditimpa bila baris ini tidak ada.
        DB::table('setting_webs')->updateOrInsert(['id' => 1], [
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
        ]);

        // Tanpa policy "disabled", middleware inbound.whitelist menolak
        // request webhook dengan 403 dan test tidak pernah menyentuh adapter.
        InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'disabled',
            'is_active' => true,
        ]);
    }

    private function chat(array $overrides = []): array
    {
        return array_merge([
            'id' => -1001234567890,
            'title' => 'Test Jasakoding',
            'type' => 'supergroup',
        ], $overrides);
    }

    private function member(array $overrides = []): array
    {
        return array_merge([
            'id' => 555001,
            'is_bot' => false,
            'first_name' => 'Budi',
        ], $overrides);
    }

    private function webhook(array $message): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/webhooks/bot/telegram', [
            'update_id' => random_int(100000, 999999),
            'message' => $message,
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secret']);
    }

    // ---------------------------------------------------------------
    // resolveText — tanpa memanggil Telegram
    // ---------------------------------------------------------------

    public function test_default_template_substitutes_member_name(): void
    {
        $resolved = app(TelegramWelcomeService::class)->resolveText($this->member(), $this->chat());

        $this->assertTrue($resolved['ok']);
        $this->assertStringContainsString('Budi', $resolved['text']);
        $this->assertStringContainsString('Test Jasakoding', $resolved['text']);
        $this->assertStringNotContainsString('{nama}', $resolved['text']);
    }

    public function test_member_without_name_is_rejected_not_greeted_blank(): void
    {
        // Jangan pernah mengirim "Halo ," — lebih baik tidak menyapa.
        $resolved = app(TelegramWelcomeService::class)->resolveText(
            ['id' => 1, 'is_bot' => false, 'first_name' => '', 'last_name' => ''],
            $this->chat(),
        );

        $this->assertFalse($resolved['ok']);
        $this->assertSame('', $resolved['text']);
    }

    public function test_full_name_is_joined_and_username_used_as_fallback(): void
    {
        $service = app(TelegramWelcomeService::class);

        $this->assertSame('Budi Santoso', $service->displayName(['first_name' => 'Budi', 'last_name' => 'Santoso']));
        $this->assertSame('@budisan', $service->displayName(['first_name' => '', 'username' => 'budisan']));
    }

    public function test_name_with_underscore_is_escaped_for_markdown(): void
    {
        // "Budi_Pratama" tanpa escape akan merusak parse_mode Markdown.
        $resolved = app(TelegramWelcomeService::class)->resolveText($this->member(['first_name' => 'Budi_Pratama']), $this->chat());

        $this->assertTrue($resolved['ok']);
        $this->assertStringContainsString('Budi\_Pratama', $resolved['text']);
    }

    public function test_custom_template_placeholders_are_used(): void
    {
        config(['services.telegram-bot-api.telegram_welcome_template' => 'Yo {nama}! Gabung grup {grup}.']);

        $resolved = app(TelegramWelcomeService::class)->resolveText($this->member(), $this->chat());

        $this->assertSame('Yo Budi! Gabung grup Test Jasakoding.', $resolved['text']);
    }

    // ---------------------------------------------------------------
    // greet — perilaku pengiriman
    // ---------------------------------------------------------------

    public function test_no_message_when_welcome_disabled(): void
    {
        Http::fake();

        $result = app(TelegramWelcomeService::class)->greet($this->member(), $this->chat());

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_welcome_sent_to_main_chat_without_thread_id(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram-bot-api.telegram_welcome_enabled' => true]);

        $result = app(TelegramWelcomeService::class)->greet($this->member(), $this->chat());

        $this->assertTrue($result['ok']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === -1001234567890
                && ! array_key_exists('message_thread_id', $request->data());
        });
    }

    public function test_welcome_targets_configred_topic(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        config([
            'services.telegram-bot-api.telegram_welcome_enabled' => true,
            'services.telegram-bot-api.telegram_welcome_thread_id' => 12,
        ]);

        app(TelegramWelcomeService::class)->greet($this->member(), $this->chat());

        Http::assertSent(fn ($request) => $request['message_thread_id'] === 12);
    }

    public function test_welcome_failure_does_not_throw(): void
    {
        Http::fake(['*' => Http::response(['ok' => false, 'description' => 'chat not found'], 400)]);
        config(['services.telegram-bot-api.telegram_welcome_enabled' => true]);

        $result = app(TelegramWelcomeService::class)->greet($this->member(), $this->chat());

        $this->assertFalse($result['ok']);
        $this->assertSame('chat not found', $result['error']);
    }

    // ---------------------------------------------------------------
    // Webhook end-to-end
    // ---------------------------------------------------------------

    public function test_webhook_greets_new_member(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram-bot-api.telegram_welcome_enabled' => true]);

        $this->webhook([
            'message_id' => 10,
            'chat' => $this->chat(),
            'from' => $this->member(['id' => 555001]),
            'new_chat_members' => [$this->member()],
        ])->assertOk()->assertJson(['status' => 'welcome_sent', 'greeted' => 1]);

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage'));
    }

    public function test_webhook_does_not_greet_when_bot_itself_joins(): void
    {
        Http::fake();
        config(['services.telegram-bot-api.telegram_welcome_enabled' => true]);

        // Saat bot diundang, new_chat_members memuat BOT itu sendiri.
        $this->webhook([
            'message_id' => 11,
            'chat' => $this->chat(),
            'new_chat_members' => [$this->member(['id' => 999, 'is_bot' => true, 'first_name' => 'jasakoding_bot'])],
        ])->assertOk()->assertJson(['status' => 'ignored', 'greeted' => 0]);

        Http::assertNothingSent();
    }

    public function test_webhook_greets_human_but_skips_bot_in_same_update(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);
        config(['services.telegram-bot-api.telegram_welcome_enabled' => true]);

        $this->webhook([
            'message_id' => 12,
            'chat' => $this->chat(),
            'new_chat_members' => [
                $this->member(['id' => 999, 'is_bot' => true, 'first_name' => 'jasakoding_bot']),
                $this->member(['id' => 555002, 'first_name' => 'Siti']),
            ],
        ])->assertOk()->assertJson(['status' => 'welcome_sent', 'greeted' => 1]);

        Http::assertSentCount(1);
    }

    public function test_webhook_ignores_membership_update_when_disabled(): void
    {
        Http::fake();

        $this->webhook([
            'message_id' => 13,
            'chat' => $this->chat(),
            'new_chat_members' => [$this->member()],
        ])->assertOk()->assertJson(['status' => 'ignored']);

        Http::assertNothingSent();
    }

    public function test_ordinary_group_message_still_works_normally(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        // Pesan biasa di grup tidak boleh dianggap event member baru.
        $this->webhook([
            'message_id' => 14,
            'chat' => $this->chat(),
            'from' => $this->member(),
            'text' => '/menu',
        ])->assertOk();
    }
}
