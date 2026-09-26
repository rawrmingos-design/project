<?php

namespace App\Services\Bot;

use App\Support\TelegramMarkdown;
use App\Support\TelegramRequiredChannels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramChannelMembershipService
{
    public const STATUS_ALLOWED = 'allowed';
    public const STATUS_NOT_MEMBER = 'not_member';
    public const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * Bot sendiri TIDAK BISA membaca daftar anggota channel (belum jadi
     * anggota, atau belum diberi hak admin).
     *
     * Dibedakan dari STATUS_UNAVAILABLE karena artinya jauh berbeda:
     * `unavailable` = gangguan sesaat (timeout/5xx) -> "coba lagi" masuk akal,
     * `misconfigured` = salah setelan yang TIDAK akan sembuh sendiri ->
     * menyuruh user "coba lagi" hanya membuatnya mencoba tanpa hasil
     * selamanya, dan admin tidak pernah dapat sinyal untuk memperbaikinya.
     */
    public const STATUS_MISCONFIGURED = 'misconfigured';

    /**
     * Balasan Telegram yang menandakan masalah SETELAN, bukan gangguan.
     *
     * Terverifikasi langsung ke Bot API:
     *   - bot bukan anggota / bukan admin -> "member list is inaccessible"
     *   - id channel salah / channel dihapus -> "chat not found"
     */
    private const BOT_ACCESS_ERROR_MARKERS = [
        'member list is inaccessible',
        'chat not found',
        'bot is not a member',
        'not enough rights',
        'chat_admin_required',
        'have no rights to get chat member',
    ];

    /**
     * Hasil pemeriksaan keanggotaan.
     *
     * `missing` berisi channel yang BELUM diikuti (hanya terisi saat
     * status not_member) supaya pesan bisa menampilkan tombol Gabung
     * untuk tiap channel yang kurang.
     *
     * `misconfigured` berisi id channel yang TIDAK BISA diperiksa bot karena
     * masalah setelan (bot belum jadi anggota/admin, atau id channel salah).
     *
     * @return array{status: string, channel_url: ?string, channels: array<int, array{id: string, url: string, label: string}>, missing: array<int, array{id: string, url: string, label: string}>, misconfigured: array<int, string>}
     */
    public function check(array $context): array
    {
        if (($context['source'] ?? null) !== 'telegram_gateway' || ! $this->isEnabled()) {
            return $this->allowedResult();
        }

        $token = trim((string) config('services.telegram-bot-api.token'));
        $userId = filter_var($context['telegram_user_id'] ?? null, FILTER_VALIDATE_INT);

        if ($token === '' || $userId === false) {
            Log::error('Telegram channel membership configuration is invalid.', [
                'token_configured' => $token !== '',
                'telegram_user_id_valid' => $userId !== false,
            ]);

            return $this->unavailableResult();
        }

        $channels = TelegramRequiredChannels::all();

        if ($channels === []) {
            Log::error('Telegram channel membership requested but no valid channel is configured.');

            return $this->unavailableResult();
        }

        $missing = [];
        $unverified = false;
        $misconfigured = false;

        foreach ($channels as $channel) {
            $verdict = $this->checkChannel($token, $channel, (int) $userId);

            if ($verdict === self::STATUS_NOT_MEMBER) {
                $missing[] = $channel;

                continue;
            }

            if ($verdict === self::STATUS_MISCONFIGURED) {
                $misconfigured = true;

                continue;
            }

            if ($verdict === self::STATUS_UNAVAILABLE) {
                $unverified = true;
            }
        }

        if ($missing !== []) {
            // Penolakan definitif menang atas gangguan: user tetap diminta
            // bergabung ke channel yang kurang.
            return [
                'status' => self::STATUS_NOT_MEMBER,
                'channel_url' => $missing[0]['url'],
                'channels' => $channels,
                'missing' => $missing,
                'misconfigured' => [],
            ];
        }

        if ($misconfigured) {
            // Setelan salah TIDAK akan sembuh sendiri. Laporkan channel mana
            // yang bermasalah supaya admin bisa langsung memperbaiki, dan
            // user tidak disuruh "coba lagi" tanpa akhir.
            $misconfiguredChannels = array_map(
                static fn (array $channel): string => (string) $channel['id'],
                $channels,
            );

            $this->alertAdmins($misconfiguredChannels);

            return [
                'status' => self::STATUS_MISCONFIGURED,
                'channel_url' => null,
                'channels' => $channels,
                'missing' => [],
                'misconfigured' => $misconfiguredChannels,
            ];
        }

        if ($unverified) {
            return [
                'status' => self::STATUS_UNAVAILABLE,
                'channel_url' => null,
                'channels' => $channels,
                'missing' => [],
                'misconfigured' => [],
            ];
        }

        return $this->allowedResult($channels);
    }

    /**
     * @param array{id: string, url: string, label: string} $channel
     * @return string salah satu STATUS_ALLOWED | STATUS_NOT_MEMBER | STATUS_UNAVAILABLE
     */
    private function checkChannel(string $token, array $channel, int $userId): string
    {
        $cacheKey = $this->cacheKey($channel['id'], $userId);

        // PENTING: keanggotaan SELALU dicek ulang ke Telegram pada SETIAP
        // request. Sebelumnya hasil positif di-cache (120 detik) DAN ada
        // "grace record" 24 jam yang diperiksa LEBIH DULU sebelum memanggil
        // Telegram — akibatnya user yang sudah KELUAR dari channel tetap
        // lolos gate sampai 24 jam.
        //   Reproduksi: join → chat/auto-order → leave → /start → tetap lolos.
        // Pencabutan keanggotaan harus langsung terasa, jadi jalan pintas itu
        // dihapus. Grace record kini HANYA dipakai sebagai cadangan ketika
        // pemeriksaan GAGAL karena gangguan sesaat (lihat blok failure di
        // bawah), supaya outage Telegram tidak mengunci user yang sah.
        try {
            $response = $this->requestChatMember($token, $channel['id'], $userId);
            $payload = $response->json();

            if (! $response->successful() || ! is_array($payload) || ($payload['ok'] ?? false) !== true) {
                $description = (string) ($payload['description'] ?? '');

                // Telegram menjawab, tapi BUKAN karena user. Ini masalah
                // setelan (bot belum ada di channel / id channel salah) yang
                // tidak akan sembuh sendiri — bedakan dari gangguan sesaat.
                if ($this->isBotAccessError($description)) {
                    Log::error('Telegram channel membership cannot be checked: bot has no access to the channel.', [
                        'channel' => $channel['id'],
                        'http_status' => $response->status(),
                        'telegram_description' => $description,
                    ]);

                    return self::STATUS_MISCONFIGURED;
                }

                Log::warning('Telegram channel membership verification failed.', [
                    'channel' => $channel['id'],
                    'http_status' => $response->status(),
                    'telegram_ok' => is_array($payload) ? ($payload['ok'] ?? null) : null,
                ]);

                return $this->transientFailureVerdict($cacheKey);
            }

            $status = (string) data_get($payload, 'result.status', '');
            $isMember = in_array($status, ['creator', 'administrator', 'member'], true)
                || ($status === 'restricted' && data_get($payload, 'result.is_member') === true);

            if ($isMember) {
                $this->rememberVerified($cacheKey);

                return self::STATUS_ALLOWED;
            }

            if (in_array($status, ['left', 'kicked', 'restricted'], true)) {
                // Penolakan DEFINITIF: Telegram menjawab, user memang tidak
                // bergabung. Grace record lama tidak boleh menahannya.
                Cache::forget($this->graceKey($cacheKey));

                return self::STATUS_NOT_MEMBER;
            }

            Log::warning('Telegram channel membership returned an unknown status.', [
                'channel' => $channel['id'],
                'membership_status' => $status,
            ]);

            return self::STATUS_UNAVAILABLE;
        } catch (Throwable $exception) {
            Log::warning('Telegram channel membership request failed.', [
                'channel' => $channel['id'],
                'exception' => $exception::class,
            ]);

            return $this->transientFailureVerdict($cacheKey);
        }
    }

    /**
     * Apakah balasan Telegram menandakan bot sendiri tidak punya akses
     * ke channel (masalah setelan), bukan gangguan sesaat?
     */
    private function isBotAccessError(string $description): bool
    {
        if (trim($description) === '') {
            return false;
        }

        $haystack = strtolower($description);

        foreach (self::BOT_ACCESS_ERROR_MARKERS as $marker) {
            if (str_contains($haystack, $marker)) {
                return true;
            }
        }

        return false;
    }

    /**
     * HEALTH CHECK (dipakai admin / command): bisakah BOT melihat keanggotaan
     * channel ini?

     * Kita periksa keanggotaan BOT SENDIRI, bukan user, karena Telegram hanya
     * mengizinkan `getChatMember` kalau bot sudah ada di channel dengan hak
     * cukup. Kalau bot saja tidak terbaca, seluruh gate pasti gagal untuk
     * SEMUA user — dan itu murni masalah setelan.
     *
     * @return array{reachable: bool, status: ?string, error: ?string}
     */
    public function probeBotAccess(string $channelId, ?string $token = null): array
    {
        $token = trim((string) ($token ?? config('services.telegram-bot-api.token')));

        if ($token === '') {
            return ['reachable' => false, 'status' => null, 'error' => 'token bot belum diisi'];
        }

        try {
            $me = Http::connectTimeout(3)->timeout(8)
                ->post("https://api.telegram.org/bot{$token}/getMe")
                ->json();

            $botId = filter_var(data_get($me, 'result.id'), FILTER_VALIDATE_INT);

            if ($botId === false) {
                return ['reachable' => false, 'status' => null, 'error' => 'tidak bisa membaca identitas bot (getMe gagal)'];
            }

            $response = $this->requestChatMember($token, $channelId, $botId);
            $payload = $response->json();

            if ($response->successful() && is_array($payload) && ($payload['ok'] ?? false) === true) {
                return [
                    'reachable' => true,
                    'status' => (string) data_get($payload, 'result.status', ''),
                    'error' => null,
                ];
            }

            return [
                'reachable' => false,
                'status' => null,
                'error' => (string) ($payload['description'] ?? 'respons Telegram tidak dikenali'),
            ];
        } catch (Throwable $exception) {
            return ['reachable' => false, 'status' => null, 'error' => $exception::class];
        }
    }

    /**
     * Beri tahu admin bahwa gate TIDAK BISA berfungsi karena masalah setelan.
     *
     * Dibatasi 1 pesan per jam per channel: masalah ini bertahan sampai admin
     * memperbaikinya, dan user yang mencoba berkali-kali tidak boleh
     * membanjiri admin dengan pesan yang sama.
     *
     * @param array<int, string> $channelIds
     */
    private function alertAdmins(array $channelIds): void
    {
        $chatId = trim((string) config('services.telegram-bot-api.admin_alert_chat_id', ''));
        $token = trim((string) config('services.telegram-bot-api.token'));

        if ($chatId === '' || $token === '') {
            // Tidak ada tujuan alert: log error di atas sudah jadi sinyalnya.
            return;
        }

        foreach ($channelIds as $channelId) {
            $guardKey = 'telegram:gate-misconfigured-alert:' . hash('sha256', $channelId);

            if (! Cache::add($guardKey, true, 3600)) {
                continue;
            }

            try {
                Http::connectTimeout(3)->timeout(8)
                    ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => TelegramMarkdown::format(implode("\n", [
                            '🚨 *Gerbang Wajib Gabung Bermasalah*',
                            '',
                            "Bot tidak bisa memeriksa keanggotaan channel {$channelId},",
                            'jadi SEMUA user tertahan di gerbang.',
                            '',
                            'Penyebab paling sering: bot belum jadi anggota channel,',
                            'atau belum diberi hak *Admin*.',
                            '',
                            'Perbaiki di pengaturan channel, lalu jalankan',
                            '`php artisan bot:gate-check` untuk memastikan.',
                        ])),
                        'parse_mode' => 'MarkdownV2',
                    ]);
            } catch (Throwable $exception) {
                Log::warning('Telegram gate misconfiguration alert could not be sent.', [
                    'channel' => $channelId,
                    'exception' => $exception::class,
                ]);
            }
        }
    }

    /**
     * Keanggotaan TIDAK BISA dicek ke Telegram karena gangguan sesaat.
     *
     * Di sini — dan HANYA di sini — "grace record" dipakai: kalau user ini
     * pernah terbukti bergabung, kita percayai fakta lama itu daripada
     * mengunci user yang sah akibat Telegram/jaringan sedang bermasalah.
     *
     * Sebaliknya, kalau user TIDAK punya riwayat pernah bergabung, gangguan
     * tetap berarti "belum bisa dipastikan" (fail-closed) — bukan lolos.
     */
    private function transientFailureVerdict(string $cacheKey): string
    {
        if (Cache::get($this->graceKey($cacheKey)) === true) {
            Log::info('Telegram channel membership unavailable; trusting previous verified membership.', [
                'cache_key' => $cacheKey,
            ]);

            return self::STATUS_ALLOWED;
        }

        return self::STATUS_UNAVAILABLE;
    }

    /**
     * Catat bahwa user ini TERBUKTI bergabung.
     *
     * Hasilnya sengaja TIDAK dipakai untuk meloloskan request berikutnya —
     * setiap request tetap dicek ke Telegram. Rekaman ini hanya jaring
     * pengaman saat Telegram sedang tidak bisa dihubungi (lihat
     * `transientFailureVerdict()`), dan dihapus begitu Telegram menyatakan
     * user sudah keluar.
     */
    private function rememberVerified(string $cacheKey): void
    {
        Cache::put(
            $this->graceKey($cacheKey),
            true,
            max(60, (int) config('services.telegram-bot-api.required_channel.grace_seconds', 86400)),
        );
    }

    private function graceKey(string $cacheKey): string
    {
        return $cacheKey . ':verified';
    }

    /**
     * Ambil keanggotaan dengan satu kali percobaan ulang. Timeout 4 detik
     * terbukti kadang tidak cukup (Telegram lambat / jaringan berkedip),
     * dan sebelumnya satu kedipan langsung memblokir seluruh perintah.
     */
    private function requestChatMember(string $token, string $channelId, int $userId): \Illuminate\Http\Client\Response
    {
        try {
            return Http::connectTimeout(3)
                ->timeout(6)
                ->post("https://api.telegram.org/bot{$token}/getChatMember", [
                    'chat_id' => $channelId,
                    'user_id' => $userId,
                ]);
        } catch (Throwable $exception) {
            return Http::connectTimeout(3)
                ->timeout(6)
                ->retry(1, 250, throw: false)
                ->post("https://api.telegram.org/bot{$token}/getChatMember", [
                    'chat_id' => $channelId,
                    'user_id' => $userId,
                ]);
        }
    }

    private function isEnabled(): bool
    {
        return filter_var(
            config('services.telegram-bot-api.required_channel.enabled', false),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    /**
     * @return array{status: string, channel_url: ?string, channels: array, missing: array}
     */
    private function allowedResult(?array $channels = null): array
    {
        return [
            'status' => self::STATUS_ALLOWED,
            'channel_url' => null,
            'channels' => $channels ?? [],
            'missing' => [],
            'misconfigured' => [],
        ];
    }

    /**
     * @return array{status: string, channel_url: ?string, channels: array, missing: array}
     */
    private function unavailableResult(): array
    {
        return [
            'status' => self::STATUS_UNAVAILABLE,
            'channel_url' => null,
            'channels' => [],
            'missing' => [],
            'misconfigured' => [],
        ];
    }

    private function cacheKey(string $channelId, int $userId): string
    {
        return 'telegram:required-channel:' . hash('sha256', $channelId . '|' . $userId);
    }
}
