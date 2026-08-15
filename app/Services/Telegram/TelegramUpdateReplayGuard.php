<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\DB;

class TelegramUpdateReplayGuard
{
    public function claim(string $botScope, mixed $updateId): bool
    {
        $botScope = trim($botScope);
        $updateId = filter_var($updateId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        if ($botScope === '' || $updateId === false) {
            return true;
        }

        return DB::table('telegram_update_receipts')->insertOrIgnore([
            'bot_scope' => mb_substr($botScope, 0, 100),
            'update_id' => $updateId,
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }
}
