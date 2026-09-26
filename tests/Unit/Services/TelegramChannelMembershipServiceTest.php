<?php

namespace Tests\Unit\Services;

use App\Services\Bot\TelegramChannelMembershipService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramChannelMembershipServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
        config([
            'services.telegram-bot-api.token' => 'test-token',
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@testchannel', 'url' => 'https://t.me/testchannel'],
            ],
        ]);
    }

    public function test_disabled_enforcement_allows_without_request(): void
    {
        config(['services.telegram-bot-api.required_channel.enabled' => false]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
        Http::assertNothingSent();
    }

    public function test_non_telegram_source_allows_without_request(): void
    {
        $result = app(TelegramChannelMembershipService::class)->check([
            'source' => 'whatsapp_gateway',
            'external_user_id' => 'whatsapp:628123',
        ]);

        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
        Http::assertNothingSent();
    }

    public function test_member_statuses_are_allowed(): void
    {
        foreach (['creator', 'administrator', 'member'] as $index => $status) {
            Cache::flush();
            Http::fake([
                '*' => Http::response([
                    'ok' => true,
                    'result' => ['status' => $status],
                ]),
            ]);

            $result = app(TelegramChannelMembershipService::class)->check($this->context(100 + $index));

            $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
        }
    }

    public function test_restricted_member_is_allowed(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'restricted', 'is_member' => true],
            ]),
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $result['status']);
    }

    public function test_non_member_statuses_are_denied(): void
    {
        foreach (['left', 'kicked'] as $index => $status) {
            Cache::flush();
            Http::fake([
                '*' => Http::response([
                    'ok' => true,
                    'result' => ['status' => $status],
                ]),
            ]);

            $result = app(TelegramChannelMembershipService::class)->check($this->context(200 + $index));

            $this->assertSame(TelegramChannelMembershipService::STATUS_NOT_MEMBER, $result['status']);
            $this->assertSame('https://t.me/testchannel', $result['channel_url']);
        }
    }

    public function test_restricted_non_member_is_denied(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'restricted', 'is_member' => false],
            ]),
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_NOT_MEMBER, $result['status']);
    }

    public function test_invalid_configuration_fails_closed(): void
    {
        // Gate AKTIF tapi daftar channel kosong (atau semua entri rusak).
        // Tidak boleh fail-open: user tanpa channel wajib yang sah harus
        // ditahan, bukan dibiarkan lewat.
        config(['services.telegram-bot-api.required_channel.channels' => []]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
        Http::assertNothingSent();
    }

    public function test_failed_and_malformed_responses_fail_closed(): void
    {
        foreach ([
            Http::response(['ok' => false], 200),
            Http::response(['message' => 'server error'], 500),
            Http::response('not-json', 200),
        ] as $index => $response) {
            Cache::flush();
            Http::fake(['*' => $response]);

            $result = app(TelegramChannelMembershipService::class)->check($this->context(300 + $index));

            $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
        }
    }

    public function test_connection_failure_fails_closed(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('timeout');
        });

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
    }

    /**
     * Bot belum jadi anggota / belum diberi hak admin di channel wajib.
     *
     * Ini BUKAN gangguan sesaat: menyuruh user "coba lagi" hanya membuatnya
     * mencoba tanpa hasil selamanya. Harus dibedakan agar admin dapat sinyal.
     */
    public function test_bot_without_channel_access_is_reported_as_misconfigured_not_transient(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: member list is inaccessible',
            ], 400),
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_MISCONFIGURED, $result['status']);
        $this->assertSame(['@testchannel'], $result['misconfigured']);
        $this->assertSame([], $result['missing'], 'Tidak boleh menyuruh user join saat masalahnya di setelan.');
    }

    /**
     * Channel salah ketik / sudah dihapus — juga masalah setelan.
     */
    public function test_unknown_channel_is_reported_as_misconfigured(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => false,
                'error_code' => 400,
                'description' => 'Bad Request: chat not found',
            ], 400),
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_MISCONFIGURED, $result['status']);
    }

    /**
     * Gangguan biasa (5xx tanpa deskripsi khas) HARUS tetap `unavailable`
     * supaya user tetap disuruh mencoba lagi — jangan sampai masalah
     * setelan menelan gangguan sementara.
     */
    public function test_generic_server_error_stays_transient(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => false, 'description' => 'Internal Server Error'], 500),
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
        $this->assertSame([], $result['misconfigured']);
    }

    /**
     * User yang memang BELUM bergabung tetap dapat pesan "Akses Terbatas",
     * walau ada channel lain yang bot-nya bermasalah. Penolakan definitif
     * tidak boleh tertutup oleh masalah setelan.
     */
    public function test_definitive_not_member_wins_over_misconfigured_channel(): void
    {
        config([
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@testchannel', 'url' => 'https://t.me/testchannel'],
                ['id' => '@brokenchannel', 'url' => 'https://t.me/brokenchannel'],
            ],
        ]);

        Http::fake([
            'https://api.telegram.org/bottest-token/getChatMember' => function ($request) {
                if (($request['chat_id'] ?? '') === '@brokenchannel') {
                    return Http::response([
                        'ok' => false,
                        'description' => 'Bad Request: member list is inaccessible',
                    ], 400);
                }

                return Http::response(['ok' => true, 'result' => ['status' => 'left']]);
            },
        ]);

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_NOT_MEMBER, $result['status']);
        $this->assertSame('@testchannel', $result['missing'][0]['id']);
    }

    /**
     * Admin harus diberi tahu, dan TIDAK dibanjiri: user yang mencoba
     * berkali-kali tidak boleh memicu pesan berulang.
     */
    public function test_admin_is_alerted_once_per_channel_when_gate_cannot_work(): void
    {
        config([
            'services.telegram-bot-api.admin_alert_chat_id' => '555000111',
        ]);

        Http::fake([
            'https://api.telegram.org/bottest-token/getChatMember' => Http::response([
                'ok' => false,
                'description' => 'Bad Request: member list is inaccessible',
            ], 400),
            'https://api.telegram.org/bottest-token/sendMessage' => Http::response(['ok' => true]),
        ]);

        $service = app(TelegramChannelMembershipService::class);

        // Tiga user berbeda — masalahnya sama, alert harus tetap satu.
        foreach ([11, 22, 33] as $userId) {
            $service->check($this->context($userId));
        }

        // Alert dibatasi 1x per jam per channel: percobaan user yang
        // berulang tidak boleh membanjiri admin dengan pesan identik.
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/sendMessage'));

        $this->assertSame(1, collect(Http::recorded())
            ->filter(fn (array $pair): bool => str_contains($pair[0]->url(), '/sendMessage'))
            ->count(), 'Alert admin harus dikirim TEPAT satu kali walau ada 3 percobaan user.');
    }

    public function test_probe_bot_access_reports_bot_membership_status(): void
    {
        Http::fake([
            'https://api.telegram.org/bottest-token/getMe' => Http::response([
                'ok' => true,
                'result' => ['id' => 4242, 'username' => 'testbot'],
            ]),
            'https://api.telegram.org/bottest-token/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'administrator'],
            ]),
        ]);

        $probe = app(TelegramChannelMembershipService::class)->probeBotAccess('@testchannel');

        $this->assertTrue($probe['reachable']);
        $this->assertSame('administrator', $probe['status']);
        $this->assertNull($probe['error']);
    }

    public function test_probe_bot_access_surfaces_the_telegram_reason_on_failure(): void
    {
        Http::fake([
            'https://api.telegram.org/bottest-token/getMe' => Http::response([
                'ok' => true,
                'result' => ['id' => 4242, 'username' => 'testbot'],
            ]),
            'https://api.telegram.org/bottest-token/getChatMember' => Http::response([
                'ok' => false,
                'description' => 'Bad Request: member list is inaccessible',
            ], 400),
        ]);

        $probe = app(TelegramChannelMembershipService::class)->probeBotAccess('@testchannel');

        $this->assertFalse($probe['reachable']);
        $this->assertStringContainsString('member list is inaccessible', (string) $probe['error']);
    }

    /**
     * BUG YANG PERNAH TERJADI: hasil positif di-cache 120 detik DAN ada
     * "grace record" 24 jam yang diperiksa lebih dulu sebelum memanggil
     * Telegram. Akibatnya user yang sudah KELUAR dari channel tetap lolos
     * gate sampai 24 jam.
     *   Reproduksi: join → auto-order → leave → /start → tetap lolos.
     *
     * Keanggotaan sekarang diperiksa ulang ke Telegram pada SETIAP request,
     * jadi pencabutan langsung terasa.
     */
    public function test_membership_is_rechecked_on_every_request(): void
    {
        Http::fake([
            '*' => Http::response([
                'ok' => true,
                'result' => ['status' => 'member'],
            ]),
        ]);

        $service = app(TelegramChannelMembershipService::class);

        $service->check($this->context());
        $service->check($this->context());

        // Dua request = dua pemeriksaan. Tidak ada jalan pintas cache.
        Http::assertSentCount(2);
    }

    /**
     * Skenario persis yang dilaporkan: user join, lalu KELUAR. Request
     * berikutnya harus ditahan — bukan diloloskan oleh rekaman lama.
     */
    public function test_user_who_leaves_the_channel_is_blocked_on_the_next_request(): void
    {
        $joined = true;

        Http::fake(function ($request) use (&$joined) {
            if (str_contains($request->url(), 'getChatMember')) {
                return Http::response([
                    'ok' => true,
                    'result' => ['status' => $joined ? 'member' : 'left'],
                ]);
            }

            return Http::response(['ok' => true]);
        });

        $service = app(TelegramChannelMembershipService::class);

        $first = $service->check($this->context());
        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $first['status']);

        // User keluar dari channel.
        $joined = false;

        $second = $service->check($this->context());
        $this->assertSame(
            TelegramChannelMembershipService::STATUS_NOT_MEMBER,
            $second['status'],
            'User yang sudah keluar TIDAK boleh lolos gate pada request berikutnya.',
        );
    }

    /**
     * Rekaman "pernah bergabung" tetap berguna — tapi HANYA saat Telegram
     * sedang tidak bisa dihubungi. Outage sesaat tidak boleh mengunci user
     * yang jelas-jelas sudah bergabung.
     */
    public function test_previous_membership_is_trusted_only_when_telegram_is_unreachable(): void
    {
        $joined = true;

        Http::fake(function ($request) use (&$joined) {
            if (str_contains($request->url(), 'getChatMember')) {
                if ($joined) {
                    return Http::response([
                        'ok' => true,
                        'result' => ['status' => 'member'],
                    ]);
                }

                throw new ConnectionException('timeout');
            }

            return Http::response(['ok' => true]);
        });

        $service = app(TelegramChannelMembershipService::class);

        $first = $service->check($this->context());
        $this->assertSame(TelegramChannelMembershipService::STATUS_ALLOWED, $first['status']);

        // Telegram mendadak tidak bisa dihubungi.
        $joined = false;

        $second = $service->check($this->context());
        $this->assertSame(
            TelegramChannelMembershipService::STATUS_ALLOWED,
            $second['status'],
            'Gangguan sesaat tidak boleh mengunci user yang sudah terbukti bergabung.',
        );
    }

    /**
     * Kebalikannya juga harus benar: user TANPA riwayat bergabung tetap
     * fail-closed saat Telegram error — bukan ikut lolos.
     */
    public function test_user_without_history_stays_blocked_when_telegram_is_unreachable(): void
    {
        Http::fake(function (): void {
            throw new ConnectionException('timeout');
        });

        $result = app(TelegramChannelMembershipService::class)->check($this->context());

        $this->assertSame(TelegramChannelMembershipService::STATUS_UNAVAILABLE, $result['status']);
    }

    private function context(int $userId = 12345): array
    {
        return [
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:' . $userId,
            'telegram_user_id' => $userId,
        ];
    }
}
