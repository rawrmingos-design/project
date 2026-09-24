<?php

namespace App\Support;

/**
 * Resolusi identitas Telegram — SATU tempat untuk semua konsumen.
 *
 * Kontrak kanonik (bentuk yang disimpan di `pembelians.gateway_principal`):
 *
 *     telegram:<telegram_user_id>          contoh: telegram:6252007210
 *
 * Produsen identitas mengirim bentuk ber-scope (`TelegramAdapter` &
 * `BotCommandHandler::handleDeposit*`):
 *
 *     telegram:<bot_scope>:<telegram_user_id>   contoh: telegram:default:6252007210
 *
 * Sebelum helper ini ada, `CheckoutOrderService::gatewayPrincipal()` hanya
 * menerima `/^telegram:\d+$/`, sehingga bentuk ber-scope jatuh ke `null` →
 * `gateway_principal` NULL → `NotifyBotOrderStatusListener` mengirim `chat_id`
 * kosong → Telegram API menolak → notifikasi order tidak pernah sampai
 * (12 dari 19 order Telegram di staging, tanpa alarm apa pun).
 *
 * PENTING: kalau format identitas gateway berubah, ubah DI SINI saja —
 * jangan tambal di masing-masing konsumen.
 */
final class TelegramIdentity
{
    /**
     * Normalisasi identitas apa pun dari gateway ke bentuk kanonik
     * `telegram:<id>`. Mengembalikan null kalau tidak ada ID numerik valid.
     */
    public static function principal(mixed $raw): ?string
    {
        $digits = self::digits($raw);

        if ($digits === null) {
            return null;
        }

        return 'telegram:' . $digits;
    }

    /**
     * ID numerik Telegram dari identitas/principal bentuk apa pun
     * (dipakai sebagai `chat_id` chat pribadi user).
     */
    public static function digits(mixed $raw): ?string
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        // Email legacy order Telegram: "telegram:<id>@telegram.user" atau
        // "<id>@telegram.user" (bentuk yang tersimpan di kolom email_pembeli).
        if (preg_match('/^(?:telegram:)?(\d+)@telegram\.user$/i', $value, $matches) === 1) {
            return $matches[1];
        }

        // Jalur non-Telegram (mis. "whatsapp:628…") JANGAN pernah dianggap
        // identitas Telegram.
        if (stripos($value, 'whatsapp:') === 0 || str_contains($value, '@')) {
            return null;
        }

        // Bentuk ber-scope dari adapter: "telegram:<scope>:<id>".
        if (preg_match('/^telegram:[^:]+:(\d+)$/', $value, $matches) === 1) {
            return $matches[1];
        }

        // Bentuk lama: "telegram:<id>".
        if (preg_match('/^telegram:(\d+)$/', $value, $matches) === 1) {
            return $matches[1];
        }

        // ID mentah: "<id>".
        if (preg_match('/^\d+$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    /**
     * `chat_id` chat pribadi user dari principal tersimpan.
     */
    public static function chatId(mixed $principal): ?string
    {
        return self::digits($principal);
    }

    /**
     * Email legacy yang dipakai order Telegram sebelum kolom
     * `gateway_principal` ada: `telegram:<id>@telegram.user`.
     */
    public static function legacyEmail(mixed $principal): ?string
    {
        $digits = self::digits($principal);

        if ($digits === null) {
            return null;
        }

        return 'telegram:' . $digits . '@telegram.user';
    }
}
