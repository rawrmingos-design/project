<?php

namespace App\Services\Bot;

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
     * @return array{status: string, channel_url: ?string}
     */
    public function check(array $context): array
    {
        if (($context['source'] ?? null) !== 'telegram_gateway' || ! $this->isEnabled()) {
            return $this->result(self::STATUS_ALLOWED);
        }

        $token = trim((string) config('services.telegram-bot-api.token'));
        $channelId = trim((string) config('services.telegram-bot-api.required_channel.id'));
        $channelUrl = trim((string) config('services.telegram-bot-api.required_channel.url'));
        $userId = filter_var($context['telegram_user_id'] ?? null, FILTER_VALIDATE_INT);

        if ($token === '' || ! $this->isValidPublicChannel($channelId, $channelUrl) || $userId === false) {
            Log::error('Telegram channel membership configuration is invalid.', [
                'token_configured' => $token !== '',
                'channel_id_configured' => $channelId !== '',
                'channel_url_configured' => $channelUrl !== '',
                'telegram_user_id_valid' => $userId !== false,
            ]);

            return $this->result(self::STATUS_UNAVAILABLE, $channelUrl);
        }

        $cacheKey = $this->cacheKey($channelId, (int) $userId);

        if (Cache::get($cacheKey) === true) {
            return $this->result(self::STATUS_ALLOWED, $channelUrl);
        }

        try {
            $response = Http::connectTimeout(2)
                ->timeout(4)
                ->post("https://api.telegram.org/bot{$token}/getChatMember", [
                    'chat_id' => $channelId,
                    'user_id' => (int) $userId,
                ]);

            $payload = $response->json();

            if (! $response->successful() || ! is_array($payload) || ($payload['ok'] ?? false) !== true) {
                Log::warning('Telegram channel membership verification failed.', [
                    'http_status' => $response->status(),
                    'telegram_ok' => is_array($payload) ? ($payload['ok'] ?? null) : null,
                ]);

                return $this->result(self::STATUS_UNAVAILABLE, $channelUrl);
            }

            $status = (string) data_get($payload, 'result.status', '');
            $isMember = in_array($status, ['creator', 'administrator', 'member'], true)
                || ($status === 'restricted' && data_get($payload, 'result.is_member') === true);

            if ($isMember) {
                Cache::put($cacheKey, true, max(1, $this->cacheSeconds()));

                return $this->result(self::STATUS_ALLOWED, $channelUrl);
            }

            if (in_array($status, ['left', 'kicked', 'restricted'], true)) {
                return $this->result(self::STATUS_NOT_MEMBER, $channelUrl);
            }

            Log::warning('Telegram channel membership returned an unknown status.', [
                'membership_status' => $status,
            ]);

            return $this->result(self::STATUS_UNAVAILABLE, $channelUrl);
        } catch (Throwable $exception) {
            Log::warning('Telegram channel membership request failed.', [
                'exception' => $exception::class,
            ]);

            return $this->result(self::STATUS_UNAVAILABLE, $channelUrl);
        }
    }

    private function isEnabled(): bool
    {
        return filter_var(
            config('services.telegram-bot-api.required_channel.enabled', false),
            FILTER_VALIDATE_BOOLEAN,
        );
    }

    private function isValidPublicChannel(string $channelId, string $channelUrl): bool
    {
        if (! preg_match('/^@[A-Za-z0-9_]{5,}$/', $channelId)) {
            return false;
        }

        if (filter_var($channelUrl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($channelUrl);

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 't.me'
            && trim((string) ($parts['path'] ?? ''), '/') === ltrim($channelId, '@');
    }

    private function cacheKey(string $channelId, int $userId): string
    {
        return 'telegram:required-channel:' . hash('sha256', $channelId . '|' . $userId);
    }

    private function cacheSeconds(): int
    {
        return max(1, (int) config('services.telegram-bot-api.required_channel.cache_seconds', 120));
    }

    /**
     * @return array{status: string, channel_url: ?string}
     */
    private function result(string $status, ?string $channelUrl = null): array
    {
        return [
            'status' => $status,
            'channel_url' => $channelUrl,
        ];
    }
}
