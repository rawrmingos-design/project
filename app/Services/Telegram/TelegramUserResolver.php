<?php

namespace App\Services\Telegram;

use App\Models\TelegramIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TelegramUserResolver
{
    public const STATUS_LINKED = 'linked';
    public const STATUS_UNLINKED = 'unlinked';
    public const STATUS_CONFLICT = 'conflict';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * @return array{status: string, user?: User, identity?: TelegramIdentity}
     */
    public function resolve(
        string $botScope,
        string|int $telegramUserId,
        string|int|null $chatId = null,
        array $metadata = [],
    ): array {
        $botScope = trim($botScope);
        $telegramUserId = trim((string) $telegramUserId);

        if ($botScope === '' || ! preg_match('/^\d{1,64}$/', $telegramUserId)) {
            return ['status' => self::STATUS_UNAVAILABLE];
        }

        $identity = TelegramIdentity::query()
            ->where('bot_scope', $botScope)
            ->where('telegram_user_id', $telegramUserId)
            ->latest('id')
            ->first();

        if (! $identity) {
            return ['status' => self::STATUS_UNLINKED];
        }

        if ($identity->revoked_at !== null || $identity->verified_at === null) {
            return ['status' => self::STATUS_REVOKED];
        }

        $user = User::query()->find($identity->user_id);
        if (! $user) {
            return ['status' => self::STATUS_UNAVAILABLE];
        }

        DB::table('telegram_identities')
            ->whereKey($identity->getKey())
            ->update(array_filter([
                'chat_id' => $chatId === null ? null : (string) $chatId,
                'username' => $this->metadataValue($metadata, 'username'),
                'first_name' => $this->metadataValue($metadata, 'first_name'),
                'last_name' => $this->metadataValue($metadata, 'last_name'),
                'last_seen_at' => now(),
                'updated_at' => now(),
            ], static fn (mixed $value): bool => $value !== null));

        return [
            'status' => self::STATUS_LINKED,
            'user' => $user,
            'identity' => $identity->fresh(),
        ];
    }

    private function metadataValue(array $metadata, string $key): ?string
    {
        $value = trim((string) ($metadata[$key] ?? ''));

        return $value === '' ? null : mb_substr($value, 0, 255);
    }
}
