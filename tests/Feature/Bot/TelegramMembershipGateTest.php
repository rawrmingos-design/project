<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotMessageFormatter;
use App\Services\Bot\TelegramChannelMembershipService;
use App\Support\TelegramRequiredChannels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gate keanggotaan Telegram mendukung BANYAK channel wajib sekaligus.
 *
 * User tidak boleh bisa membuka katalog / membuat order sebelum bergabung
 * ke SEMUA channel yang dikonfigurasi, dan pesan "Akses Terbatas" harus
 * memberi tombol Gabung untuk tiap channel yang kurang.
 */
class TelegramMembershipGateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.channels' => [],
        ]);
    }

    /**
     * @return array{0: BotMessageFormatter, 1: TelegramChannelMembershipService}
     */
    private function sut(): array
    {
        return [app(BotMessageFormatter::class), app(TelegramChannelMembershipService::class)];
    }

    /**
     * @param array<string, string> $statusPerChannel
     */
    private function fakeMembership(array $statusPerChannel): void
    {
        Http::fake([
            'https://api.telegram.org/botdummy-token/getChatMember' => function ($request) use ($statusPerChannel) {
                $chatId = (string) ($request['chat_id'] ?? '');

                if (! array_key_exists($chatId, $statusPerChannel)) {
                    return Http::response(['ok' => false, 'description' => 'chat not found'], 400);
                }

                return Http::response([
                    'ok' => true,
                    'result' => ['status' => $statusPerChannel[$chatId]],
                ]);
            },
        ]);
    }

    private function context(int $userId = 9876): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:' . $userId,
            'telegram_user_id' => $userId,
        ];
    }

    public function test_resolver_accepts_numeric_id_for_private_group(): void
    {
        // Grup privat tidak punya username, jadi satu-satunya cara gate bisa
        // bekerja adalah ID numerik. Link undangannya berbentuk +hash yang
        // TIDAK mungkin sama dengan ID — jadi keduanya tidak boleh
        // dibandingkan satu sama lain.
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '-1001234567890', 'url' => 'https://t.me/+AbCdEfGhIjK', 'label' => 'Grup Privat'],
            ],
        ]);

        $channels = TelegramRequiredChannels::all();

        $this->assertCount(1, $channels);
        $this->assertSame('-1001234567890', $channels[0]['id']);
        $this->assertSame('https://t.me/+AbCdEfGhIjK', $channels[0]['url']);
        $this->assertSame('Grup Privat', $channels[0]['label']);
    }

    public function test_resolver_accepts_legacy_joinchat_invite_link(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '-1009876543210', 'url' => 'https://t.me/joinchat/AbCdEfGhIjK'],
            ],
        ]);

        $this->assertCount(1, TelegramRequiredChannels::all());
    }

    public function test_resolver_has_no_env_fallback(): void
    {
        // Dulu nilai bisa datang dari .env, dan itu membuat admin bingung
        // karena isian panel diam-diam diabaikan. Sekarang daftar channel
        // HANYA dari DB.
        config([
            'services.telegram-bot-api.required_channel.channels' => [],
            // Nilai .env sengaja diisi: TIDAK boleh dipakai lagi.
            'services.telegram-bot-api.required_channel.id' => '@envchan',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/envchan',
        ]);

        $this->assertSame([], TelegramRequiredChannels::all());
    }

    public function test_resolver_returns_all_valid_channels(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@mastoredigital', 'url' => 'https://t.me/mastoredigital', 'label' => 'Channel Info'],
                ['id' => '@mapremiumsinfo', 'url' => 'https://t.me/mapremiumsinfo', 'label' => 'Grup Info'],
            ],
        ]);

        $channels = TelegramRequiredChannels::all();

        $this->assertCount(2, $channels);
        $this->assertSame(['@mastoredigital', '@mapremiumsinfo'], array_column($channels, 'id'));
    }

    public function test_resolver_drops_malformed_entries_instead_of_passing_them_to_telegram(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@goodchannel', 'url' => 'https://t.me/goodchannel'],
                // ID tanpa awalan @ dan bukan numerik.
                ['id' => 'bukanusername', 'url' => 'https://t.me/bukanusername'],
                // Domain selain t.me.
                ['id' => '@other', 'url' => 'https://telegram.me/other'],
                // Tanpa ID.
                ['id' => '', 'url' => 'https://t.me/kosong'],
            ],
        ]);

        $channels = TelegramRequiredChannels::all();

        $this->assertCount(1, $channels);
        $this->assertSame('@goodchannel', $channels[0]['id']);
    }

    public function test_user_missing_one_of_two_channels_is_denied_with_only_that_channel_listed(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@mastoredigital', 'url' => 'https://t.me/mastoredigital', 'label' => 'Channel Info'],
                ['id' => '@mapremiumsinfo', 'url' => 'https://t.me/mapremiumsinfo', 'label' => 'Grup Info'],
            ],
        ]);
        $this->fakeMembership([
            '@mastoredigital' => 'member',
            '@mapremiumsinfo' => 'left',
        ]);

        [, $membership] = $this->sut();
        $result = $membership->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_NOT_MEMBER, $result['status']);
        $this->assertCount(2, $result['channels']);
        $this->assertCount(1, $result['missing'], 'Hanya channel yang KURANG yang boleh ditampilkan.');
        $this->assertSame('@mapremiumsinfo', $result['missing'][0]['id']);
    }

    public function test_user_joining_all_channels_is_allowed(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@mastoredigital', 'url' => 'https://t.me/mastoredigital'],
                ['id' => '@mapremiumsinfo', 'url' => 'https://t.me/mapremiumsinfo'],
            ],
        ]);
        $this->fakeMembership([
            '@mastoredigital' => 'member',
            '@mapremiumsinfo' => 'administrator',
        ]);

        [, $membership] = $this->sut();

        $this->assertSame(
            TelegramChannelMembershipService::STATUS_ALLOWED,
            $membership->check($this->context())['status'],
        );
    }

    public function test_membership_denial_message_lists_every_missing_channel_with_join_button(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@mastoredigital', 'url' => 'https://t.me/mastoredigital', 'label' => 'Channel Info'],
                ['id' => '@mapremiumsinfo', 'url' => 'https://t.me/mapremiumsinfo', 'label' => 'Grup Info'],
            ],
        ]);
        $this->fakeMembership([
            '@mastoredigital' => 'left',
            '@mapremiumsinfo' => 'kicked',
        ]);

        [$formatter, $membership] = $this->sut();
        $message = $formatter->formatTelegramMembershipRequired(
            $membership->check($this->context())['missing'],
        );

        $this->assertStringContainsString('Akses Terbatas', $message['text']);
        $this->assertStringContainsString('@mastoredigital', $message['text']);
        $this->assertStringContainsString('@mapremiumsinfo', $message['text']);
        $this->assertStringContainsString('semua', $message['text']);

        $urls = [];

        foreach ($message['buttons'] as $row) {
            foreach ($row as $button) {
                if (isset($button['url'])) {
                    $urls[] = $button['url'];
                }
            }
        }

        $this->assertSame(
            ['https://t.me/mastoredigital', 'https://t.me/mapremiumsinfo'],
            $urls,
            'Setiap channel yang kurang harus punya tombol Gabung sendiri.',
        );

        // Tombol terakhir = tombol cek ulang, bukan URL.
        // Format internal formatter memakai key `callback`; adapter yang
        // menerjemahkannya ke `callback_data` milik Telegram.
        $lastRow = $message['buttons'][count($message['buttons']) - 1];
        $this->assertSame('menu', $lastRow[0]['callback']);
    }

    public function test_single_missing_channel_uses_singular_wording(): void
    {
        $formatter = app(BotMessageFormatter::class);

        $message = $formatter->formatTelegramMembershipRequired([
            ['id' => '@onlychannel', 'url' => 'https://t.me/onlychannel', 'label' => 'Channel Info'],
        ]);

        $this->assertStringContainsString('Akses Terbatas', $message['text']);
        // Kalimat pembuka memakai bentuk tunggal ("channel berikut").
        $this->assertStringContainsString('bergabung ke channel berikut', $message['text']);
        $this->assertStringNotContainsString('ke *semua* channel', $message['text']);
        $this->assertCount(2, $message['buttons'], '1 tombol gabung + 1 tombol cek ulang.');
    }

    public function test_empty_missing_list_falls_back_to_unavailable_message_not_empty_gate(): void
    {
        $formatter = app(BotMessageFormatter::class);

        $message = $formatter->formatTelegramMembershipRequired([]);

        // Gerbang tanpa jalan keluar lebih buruk daripada pesan gangguan.
        $this->assertStringContainsString('Verifikasi Keanggotaan Bermasalah', $message['text']);
    }
}
