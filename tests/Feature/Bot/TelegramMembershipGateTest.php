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
            'services.telegram-bot-api.required_channel.id' => null,
            'services.telegram-bot-api.required_channel.url' => null,
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

    public function test_resolver_falls_back_to_legacy_single_channel_config(): void
    {
        config([
            'services.telegram-bot-api.required_channel.id' => '@legacychan',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/legacychan',
            'services.telegram-bot-api.required_channel.channels' => [],
        ]);

        $channels = TelegramRequiredChannels::all();

        $this->assertCount(1, $channels);
        $this->assertSame('@legacychan', $channels[0]['id']);
        $this->assertSame('https://t.me/legacychan', $channels[0]['url']);
    }

    public function test_resolver_prefers_multi_channel_list_over_legacy_single(): void
    {
        config([
            'services.telegram-bot-api.required_channel.id' => '@legacychan',
            'services.telegram-bot-api.required_channel.url' => 'https://t.me/legacychan',
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@mastoredigital', 'url' => 'https://t.me/mastoredigital', 'label' => 'Channel Info'],
                ['id' => '@mapremiumsinfo', 'url' => 'https://t.me/mapremiumsinfo', 'label' => 'Grup Info'],
            ],
        ]);

        $channels = TelegramRequiredChannels::all();

        $this->assertCount(2, $channels);
        $this->assertSame(['@mastoredigital', '@mapremiumsinfo'], array_column($channels, 'id'));
    }

    public function test_resolver_drops_channel_whose_url_does_not_match_id(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@goodchannel', 'url' => 'https://t.me/goodchannel'],
                // URL menunjuk channel lain -> entri ini tidak boleh dipakai,
                // kalau tidak user diarahkan ke channel yang salah.
                ['id' => '@badchannel', 'url' => 'https://t.me/otherchannel'],
                ['id' => 'bukanusername', 'url' => 'https://t.me/bukanusername'],
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
