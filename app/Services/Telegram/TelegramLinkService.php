<?php

namespace App\Services\Telegram;

use App\Models\TelegramIdentity;
use App\Models\TelegramLinkChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelegramLinkService
{
    public const DEFAULT_EXPIRY_MINUTES = 10;

    public const DEFAULT_MAX_ATTEMPTS = 5;

    public function createChallenge(User $user): array
    {
        $botScope = $this->botScope();
        $token = Str::random(64);
        $expiresAt = now()->addMinutes($this->expiryMinutes());
        $maxAttempts = $this->maxAttempts();

        $challenge = DB::transaction(function () use ($user, $botScope, $token, $expiresAt, $maxAttempts): TelegramLinkChallenge {
            TelegramLinkChallenge::query()
                ->where('user_id', $user->getKey())
                ->where('bot_scope', $botScope)
                ->whereNull('consumed_at')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return TelegramLinkChallenge::query()->create([
                'user_id' => $user->getKey(),
                'tenant_id' => $user->tenant_id,
                'bot_scope' => $botScope,
                'token_hash' => $this->tokenHash($token),
                'expires_at' => $expiresAt,
                'max_attempts' => $maxAttempts,
            ]);
        });

        return [
            'status' => 'created',
            'challenge' => $challenge,
            'token' => $token,
            'bot_scope' => $botScope,
            'expires_at' => $expiresAt,
            'expires_in_minutes' => $this->expiryMinutes(),
            'launch_url' => $this->launchUrl($token),
        ];
    }

    public function consumeChallenge(
        string $token,
        string $telegramUserId,
        string|int|null $chatId = null,
        array $metadata = [],
        ?string $botScope = null,
    ): array {
        $token = trim($token);
        $telegramUserId = trim($telegramUserId);
        $botScope ??= $this->botScope();

        if (! $this->isValidToken($token) || ! preg_match('/^\d{1,64}$/', $telegramUserId)) {
            return $this->failure('invalid_token');
        }

        return DB::transaction(function () use ($token, $telegramUserId, $chatId, $metadata, $botScope): array {
            $challenge = TelegramLinkChallenge::query()
                ->where('bot_scope', $botScope)
                ->where('token_hash', $this->tokenHash($token))
                ->lockForUpdate()
                ->first();

            if (! $challenge) {
                return $this->failure('not_found');
            }

            if ($challenge->consumed_at !== null || $challenge->revoked_at !== null) {
                return $this->failure('replayed');
            }

            if ($challenge->expires_at?->isPast()) {
                $challenge->forceFill(['revoked_at' => now()])->save();

                return $this->failure('expired');
            }

            if ($challenge->attempts >= $challenge->max_attempts) {
                $challenge->forceFill(['revoked_at' => now()])->save();

                return $this->failure('max_attempts');
            }

            if (! $this->isValidTelegramId($telegramUserId)) {
                $this->registerFailure($challenge);

                return $this->failure('invalid_telegram_id');
            }

            $existingIdentity = TelegramIdentity::query()
                ->where('bot_scope', $botScope)
                ->where('telegram_user_id', $telegramUserId)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($existingIdentity && (int) $existingIdentity->user_id !== (int) $challenge->user_id) {
                $this->registerFailure($challenge);

                return $this->failure('identity_conflict');
            }

            $userIdentity = TelegramIdentity::query()
                ->where('user_id', $challenge->user_id)
                ->where('bot_scope', $botScope)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($userIdentity && $userIdentity->telegram_user_id !== $telegramUserId) {
                $this->registerFailure($challenge);

                return $this->failure('user_identity_conflict');
            }

            $identity = $existingIdentity ?? $userIdentity ?? new TelegramIdentity();
            $identity->forceFill([
                'user_id' => $challenge->user_id,
                'tenant_id' => $challenge->tenant_id,
                'bot_scope' => $botScope,
                'telegram_user_id' => $telegramUserId,
                'chat_id' => $chatId === null ? null : (string) $chatId,
                'username' => $this->metadataValue($metadata, 'username'),
                'first_name' => $this->metadataValue($metadata, 'first_name'),
                'last_name' => $this->metadataValue($metadata, 'last_name'),
                'linked_at' => $identity->linked_at ?? now(),
                'verified_at' => now(),
                'last_seen_at' => now(),
                'revoked_at' => null,
            ])->save();

            $challenge->forceFill(['consumed_at' => now()])->save();

            return [
                'status' => 'verified',
                'user' => User::query()->find($challenge->user_id),
                'identity' => $identity->fresh(),
                'challenge' => $challenge->fresh(),
            ];
        });
    }

    public function revokeForUser(User $user): int
    {
        return TelegramLinkChallenge::query()
            ->where('user_id', $user->getKey())
            ->where('bot_scope', $this->botScope())
            ->whereNull('consumed_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function unlink(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $now = now();
            $this->revokeForUser($user);
            TelegramIdentity::query()
                ->where('user_id', $user->getKey())
                ->where('bot_scope', $this->botScope())
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $now]);
        });
    }

    public function activeIdentityForUser(User $user): ?TelegramIdentity
    {
        return TelegramIdentity::query()
            ->where('user_id', $user->getKey())
            ->where('bot_scope', $this->botScope())
            ->whereNull('revoked_at')
            ->whereNotNull('verified_at')
            ->latest('id')
            ->first();
    }

    private function registerFailure(TelegramLinkChallenge $challenge): void
    {
        $challenge->increment('attempts');
        $challenge->refresh();

        if ($challenge->attempts >= $challenge->max_attempts) {
            $challenge->forceFill(['revoked_at' => now()])->save();
        }
    }

    private function metadataValue(array $metadata, string $key): ?string
    {
        $value = trim((string) ($metadata[$key] ?? ''));

        return $value === '' ? null : mb_substr($value, 0, 255);
    }

    private function isValidToken(string $token): bool
    {
        return strlen($token) >= 40
            && strlen($token) <= 128
            && preg_match('/^[A-Za-z0-9]+$/', $token) === 1;
    }

    private function isValidTelegramId(string $telegramUserId): bool
    {
        return filter_var($telegramUserId, FILTER_VALIDATE_INT) !== false;
    }

    private function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function botScope(): string
    {
        $scope = trim((string) config('services.telegram-bot-api.bot_scope', 'default'));

        return $scope !== '' ? mb_substr($scope, 0, 100) : 'default';
    }

    private function launchUrl(string $token): ?string
    {
        $botUsername = trim((string) config('services.telegram-bot-api.bot_username', ''));

        if ($botUsername === '') {
            return null;
        }

        return 'https://t.me/' . rawurlencode(ltrim($botUsername, '@')) . '?start=' . rawurlencode($token);
    }

    private function expiryMinutes(): int
    {
        return max(1, min(60, (int) config(
            'services.telegram-bot-api.link_challenge_expiry_minutes',
            self::DEFAULT_EXPIRY_MINUTES,
        )));
    }

    private function maxAttempts(): int
    {
        return max(1, min(255, (int) config(
            'services.telegram-bot-api.link_challenge_max_attempts',
            self::DEFAULT_MAX_ATTEMPTS,
        )));
    }

    private function failure(string $reason): array
    {
        return [
            'status' => 'failed',
            'reason' => $reason,
        ];
    }
}
