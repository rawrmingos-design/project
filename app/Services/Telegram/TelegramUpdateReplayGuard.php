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

    /**
     * Batalkan klaim supaya update yang SAMA masih bisa diproses nanti.
     *
     * Dipakai saat update sudah dicap "sudah saya tangani" tetapi ternyata
     * gagal diserahkan ke antrean — mis. Redis sedang mati. Tanpa pembatalan,
     * Telegram yang mengirim ulang (karena tidak menerima balasan) akan
     * ditolak sebagai duplikat, dan pesan pengguna hilang diam-diam. Lebih
     * baik gagal terang-terangan: kembalikan `500`, biarkan Telegram mencoba
     * lagi, dan pastikan percobaan berikutnya tidak diblokir klaim lama.
     */
    public function release(string $botScope, mixed $updateId): void
    {
        $botScope = trim($botScope);
        $updateId = filter_var($updateId, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 0],
        ]);

        if ($botScope === '' || $updateId === false) {
            return;
        }

        DB::table('telegram_update_receipts')
            ->where('bot_scope', mb_substr($botScope, 0, 100))
            ->where('update_id', $updateId)
            ->delete();
    }
}
