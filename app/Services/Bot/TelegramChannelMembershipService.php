<?php

namespace App\Services\Bot;

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
     * Hasil pemeriksaan keanggotaan.
     *
     * `missing` berisi channel yang BELUM diikuti (hanya terisi saat
     * status not_member) supaya pesan bisa menampilkan tombol Gabung
     * untuk tiap channel yang kurang.
     *
     * @return array{status: string, channel_url: ?string, channels: array<int, array{id: string, url: string, label: string}>, missing: array<int, array{id: string, url: string, label: string}>}
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

        foreach ($channels as $channel) {
            $verdict = $this->checkChannel($token, $channel, (int) $userId);

            if ($verdict === self::STATUS_NOT_MEMBER) {
                $missing[] = $channel;

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
            ];
        }

        if ($unverified) {
            return [
                'status' => self::STATUS_UNAVAILABLE,
                'channel_url' => null,
                'channels' => $channels,
                'missing' => [],
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

        if (Cache::get($cacheKey) === true) {
            return self::STATUS_ALLOWED;
        }

        // Sebelumnya user ini SUDAH terverifikasi sebagai member channel ini.
        // Simpan fakta itu lebih lama dari TTL verifikasi, supaya gangguan
        // sesaat pada Telegram tidak mengunci user yang jelas-jelas gabung.
        if (Cache::get($this->graceKey($cacheKey)) === true) {
            return self::STATUS_ALLOWED;
        }

        try {
            $response = $this->requestChatMember($token, $channel['id'], $userId);
            $payload = $response->json();

            if (! $response->successful() || ! is_array($payload) || ($payload['ok'] ?? false) !== true) {
                Log::warning('Telegram channel membership verification failed.', [
                    'channel' => $channel['id'],
                    'http_status' => $response->status(),
                    'telegram_ok' => is_array($payload) ? ($payload['ok'] ?? null) : null,
                ]);

                return self::STATUS_UNAVAILABLE;
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

            return self::STATUS_UNAVAILABLE;
        }
    }

    private function rememberVerified(string $cacheKey): void
    {
        Cache::put($cacheKey, true, max(1, $this->cacheSeconds()));
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
        ];
    }

    private function cacheKey(string $channelId, int $userId): string
    {
        return 'telegram:required-channel:' . hash('sha256', $channelId . '|' . $userId);
    }

    private function cacheSeconds(): int
    {
        return max(1, (int) config('services.telegram-bot-api.required_channel.cache_seconds', 120));
    }
}
