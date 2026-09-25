<?php

namespace Tests\Feature\Bot;

use App\Models\SettingWeb;
use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\TelegramAnnouncementService;
use App\Support\TelegramAnnouncementTargets;
use App\Support\TelegramDiscussionUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramTopicAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fixture SettingWeb dengan semua kolom NOT NULL yang wajib.
     * Diperlukan karena `storeIntro()` (dipakai formatHelp) membaca
     * `setting_webs.nomor_admin`.
     */
    private function createSetting(array $overrides = []): SettingWeb
    {
        return SettingWeb::query()->create(array_merge([
            'id' => 1,
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test description',
            'keywords' => 'topup,test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/@test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'dummy-api',
            'warna1' => '#123456',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#654321',
            'paydisini_apikey' => 'dummy-paydisini',
            'order_prefik' => 'INV',
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram-bot-api.token' => 'test-token',
            'services.telegram-bot-api.announcement_targets' => [],
            'services.telegram-bot-api.discussion_url' => null,
        ]);
    }

    // ---------------------------------------------------------------
    // Resolusi tujuan pengumuman
    // ---------------------------------------------------------------

    public function test_targets_reject_username_instead_of_numeric_chat_id(): void
    {
        config(['services.telegram-bot-api.announcement_targets' => [
            ['label' => 'Salah', 'chat_id' => '@namagrup', 'thread_id' => 12],
        ]]);

        $this->assertSame([], TelegramAnnouncementTargets::all(), 'Username publik harus ditolak untuk tujuan kirim.');
        $this->assertTrue(TelegramAnnouncementTargets::isEmpty());
    }

    public function test_targets_keep_topic_and_main_chat_separately(): void
    {
        config(['services.telegram-bot-api.announcement_targets' => [
            ['label' => 'Announcement', 'chat_id' => '-1001234567890', 'thread_id' => 12],
            ['label' => 'General', 'chat_id' => '-1001234567890', 'thread_id' => null],
        ]]);

        $targets = TelegramAnnouncementTargets::all();

        $this->assertCount(2, $targets, 'Topik dan chat utama adalah dua tujuan berbeda.');
        $this->assertSame(12, $targets[0]['thread_id']);
        $this->assertNull($targets[1]['thread_id']);
    }

    public function test_targets_dedupe_same_chat_and_thread(): void
    {
        config(['services.telegram-bot-api.announcement_targets' => [
            ['label' => 'A', 'chat_id' => '-1001234567890', 'thread_id' => 12],
            ['label' => 'A lagi', 'chat_id' => '-1001234567890', 'thread_id' => 12],
        ]]);

        $this->assertCount(1, TelegramAnnouncementTargets::all());
    }

    public function test_targets_normalize_blank_thread_to_main_chat(): void
    {
        config(['services.telegram-bot-api.announcement_targets' => [
            ['label' => 'General', 'chat_id' => '-1001234567890', 'thread_id' => ''],
        ]]);

        $this->assertNull(TelegramAnnouncementTargets::all()[0]['thread_id']);
    }

    // ---------------------------------------------------------------
    // Pengiriman
    // ---------------------------------------------------------------

    public function test_announcement_is_sent_with_message_thread_id(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        config(['services.telegram-bot-api.announcement_targets' => [
            ['label' => 'Announcement', 'chat_id' => '-1001234567890', 'thread_id' => 12],
        ]]);

        $results = app(TelegramAnnouncementService::class)->send('Restock 5 Diamond');

        $this->assertTrue($results[0]['ok']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/sendMessage')
                && $request['chat_id'] === '-1001234567890'
                && $request['message_thread_id'] === 12
                && $request['text'] === 'Restock 5 Diamond';
        });
    }

    public function test_announcement_to_main_chat_omits_thread_id(): void
    {
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        config(['services.telegram-bot-api.announcement_targets' => [
            ['label' => 'General', 'chat_id' => '-1001234567890', 'thread_id' => null],
        ]]);

        app(TelegramAnnouncementService::class)->send('Halo semua');

        Http::assertSent(function ($request) {
            // Jangan kirim message_thread_id ke chat utama.
            return str_contains($request->url(), '/sendMessage')
                && ! array_key_exists('message_thread_id', $request->data());
        });
    }

    public function test_announcement_reports_error_when_no_target_configured(): void
    {
        Http::fake();

        $results = app(TelegramAnnouncementService::class)->send('Test');

        $this->assertFalse($results[0]['ok']);
        $this->assertStringContainsString('tujuan', strtolower((string) $results[0]['error']));
        Http::assertNothingSent();
    }

    public function test_announcement_reports_error_when_token_missing(): void
    {
        Http::fake();

        config([
            'services.telegram-bot-api.token' => '',
            'services.telegram-bot-api.announcement_targets' => [
                ['label' => 'Announcement', 'chat_id' => '-1001234567890', 'thread_id' => 12],
            ],
        ]);

        $results = app(TelegramAnnouncementService::class)->send('Test');

        $this->assertFalse($results[0]['ok']);
        $this->assertFalse(app(TelegramAnnouncementService::class)->isConfigured());
        Http::assertNothingSent();
    }

    public function test_announcement_continues_to_other_targets_when_one_fails(): void
    {
        // Cocokkan lewat BODY, bukan URL — pola Http::fake hanya melihat URL.
        Http::fake(function ($request) {
            $data = $request->data();

            if (($data['message_thread_id'] ?? null) === 99) {
                return Http::response(['ok' => false, 'description' => 'Bad Request: thread not found'], 400);
            }

            return Http::response(['ok' => true], 200);
        });

        config(['services.telegram-bot-api.announcement_targets' => [
            ['label' => 'Rusak', 'chat_id' => '-1009999999999', 'thread_id' => 99],
            ['label' => 'Announcement', 'chat_id' => '-1001234567890', 'thread_id' => 12],
        ]]);

        $results = app(TelegramAnnouncementService::class)->send('Test');

        $this->assertCount(2, $results, 'Satu tujuan gagal tidak boleh menghentikan tujuan lain.');
        $this->assertFalse($results[0]['ok']);
        $this->assertTrue($results[1]['ok']);
    }

    // ---------------------------------------------------------------
    // Deep-link diskusi
    // ---------------------------------------------------------------

    public function test_discussion_url_accepts_group_and_topic_deep_links(): void
    {
        $this->assertTrue(TelegramDiscussionUrl::isValid('https://t.me/istanagrup'));
        $this->assertTrue(TelegramDiscussionUrl::isValid('https://t.me/istanagrup/12'));
    }

    public function test_discussion_url_rejects_non_telegram_host(): void
    {
        $this->assertFalse(TelegramDiscussionUrl::isValid('https://example.com/grup'));
        $this->assertFalse(TelegramDiscussionUrl::isValid('http://t.me/istanagrup'));
        $this->assertFalse(TelegramDiscussionUrl::isValid(''));
    }

    public function test_help_shows_discussion_button_only_when_configured(): void
    {
        $this->createSetting();
        $formatter = app(BotMessageFormatter::class);

        config(['services.telegram-bot-api.discussion_url' => null]);
        $without = $formatter->formatHelp();
        $this->assertStringNotContainsString('Grup Diskusi', json_encode($without['buttons']));

        config(['services.telegram-bot-api.discussion_url' => 'https://t.me/istanagrup/12']);
        $with = $formatter->formatHelp();

        $flat = collect($with['buttons'])->flatten(1);
        $discussion = $flat->firstWhere('text', '💬 Grup Diskusi');

        $this->assertNotNull($discussion, 'Tombol Diskusi harus muncul saat link diisi.');
        $this->assertSame('https://t.me/istanagrup/12', $discussion['url']);
    }
}
