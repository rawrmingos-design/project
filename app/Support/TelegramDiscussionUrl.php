<?php

namespace App\Support;

/**
 * Deep-link grup diskusi (opsional) yang dipakai bot untuk tombol
 * "Diskusi" pada pesan status/notifikasi.
 *
 * PENTING — kenapa TIDAK dibuat fitur "post otomatis ke topik":
 * Bot API TIDAK punya endpoint untuk MEMBUAT post di topik "General" dari
 * sebuah grup. `sendMessage` yang dikirim tanpa `message_thread_id` akan
 * memposting ke chat/Topik General itu sendiri; sedangkan memposting
 * sebagai pesan user tidak mungkin dilakukan bot.
 *
 * Jadi:
 *   - Mengirim ke topik (Topik Announcement)  -> didukung, lihat
 *     {@see TelegramAnnouncementTargets} + TelegramAnnouncementService.
 *   - Memberi user jalan masuk ke topik diskusi -> deep-link t.me
 *     (kelas ini).
 */
final class TelegramDiscussionUrl
{
    public static function get(): ?string
    {
        $url = trim((string) config('services.telegram-bot-api.discussion_url', ''));

        return self::isValid($url) ? $url : null;
    }

    /**
     * Hanya deep-link t.me yang sah. Menerima bentuk grup publik
     * (`https://t.me/namagrup`) maupun topik spesifik
     * (`https://t.me/namagrup/12`).
     */
    public static function isValid(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return false;
        }

        if (strtolower((string) ($parts['host'] ?? '')) !== 't.me') {
            return false;
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');

        // namagrup  atau  namagrup/12
        return preg_match('/^[A-Za-z0-9_]{4,}(\/\d+)?$/', $path) === 1;
    }
}
