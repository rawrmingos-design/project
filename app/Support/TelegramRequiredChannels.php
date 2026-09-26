<?php

namespace App\Support;

/**
 * Sumber tunggal daftar grup/channel Telegram yang WAJIB diikuti sebelum user
 * bisa membuka katalog / membuat order.
 *
 * PENTING — kenapa TIDAK ada fallback `.env`:
 * Dulu nilai bisa datang dari `.env` (`TELEGRAM_REQUIRED_CHANNEL_ID`) maupun
 * dari kolom DB. Dua sumber untuk satu kebenaran membuat admin bingung: sudah
 * mengisi di panel tapi yang dipakai `.env`, sehingga gate tampak "tidak
 * ngefek". Sekarang SATU sumber saja — kolom JSON
 * `setting_webs.telegram_required_channels` yang diisi dari panel admin.
 *
 * Saklar hidup/matinya gate TETAP di `.env`
 * (`TELEGRAM_REQUIRED_CHANNEL_ENABLED`) supaya tidak bisa dimatikan diam-diam
 * dari UI saat produksi.
 *
 * Bentuk satu entri:
 *   ['id' => '@channelku'|'-1001234567890',
 *    'url' => 'https://t.me/channelku'|'https://t.me/+AbCdEf',
 *    'label' => 'Nama tampilan']
 */
final class TelegramRequiredChannels
{
    /**
     * @return array<int, array{id: string, url: string, label: string}>
     */
    public static function all(): array
    {
        $channels = [];

        foreach ((array) config('services.telegram-bot-api.required_channel.channels', []) as $entry) {
            $channel = self::normalize(is_array($entry) ? $entry : []);

            if ($channel !== null) {
                $channels[] = $channel;
            }
        }

        return self::dedupe($channels);
    }

    /**
     * Hanya entri yang bentuknya sah. Entri rusak DIBUANG (bukan diloloskan)
     * supaya gate tidak pernah memeriksa target yang tidak bisa dibaca bot.
     *
     * @param array<string, mixed> $entry
     * @return array{id: string, url: string, label: string}|null
     */
    private static function normalize(array $entry): ?array
    {
        $id = trim((string) ($entry['id'] ?? ''));
        $url = trim((string) ($entry['url'] ?? ''));

        if (! self::isValidId($id) || ! self::isValidUrl($url)) {
            return null;
        }

        return [
            'id' => $id,
            'url' => $url,
            'label' => trim((string) ($entry['label'] ?? '')) !== ''
                ? trim((string) $entry['label'])
                : $id,
        ];
    }

    /**
     * Identitas yang diterima Bot API `getChatMember`.
     *
     *  - `@username`  -> channel/grup PUBLIK.
     *  - `-100...`    -> id numerik, satu-satunya cara untuk grup privat.
     *
     * Catatan: Bot API TIDAK bisa mengubah link undangan (`t.me/+hash`)
     * menjadi id. Karena itu kolom ID wajib diisi manual — tanpa id, gate
     * tidak mungkin bekerja.
     */
    public static function isValidId(string $id): bool
    {
        if ($id === '') {
            return false;
        }

        // Channel publik: @username (min. 5 karakter, sesuai aturan Telegram).
        if (preg_match('/^@[A-Za-z0-9_]{5,}$/', $id) === 1) {
            return true;
        }

        // Supergroup/channel numerik. Selalu negatif dan diawali -100
        // untuk supergroup; bentuk -1234567890 juga diterima untuk
        // compatibilitas grup lama.
        return preg_match('/^-\d{6,}$/', $id) === 1;
    }

    /**
     * Link yang diklik user untuk bergabung.
     *
     *  - `https://t.me/namagrup`      -> publik.
     *  - `https://t.me/+AbCdEfGh`     -> undangan privat.
     *  - `https://t.me/joinchat/xxx`  -> undangan privat (bentuk lama).
     */
    public static function isValidUrl(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($url);

        if (strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return false;
        }

        // Hanya t.me (bukan telegram.me / subdomain lain) supaya link yang
        // tampil ke user tidak bisa diarahkan ke domain lain.
        if (strtolower((string) ($parts['host'] ?? '')) !== 't.me') {
            return false;
        }

        return self::isValidInvitePath(trim((string) ($parts['path'] ?? ''), '/'));
    }

    /**
     * Path setelah t.me. Harus salah satu bentuk yang benar-benar bisa
     * membawa user masuk ke sebuah grup/channel.
     */
    public static function isValidInvitePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        // Undangan privat: +AbCdEfGh
        if (preg_match('/^\+[A-Za-z0-9_-]{8,}$/', $path) === 1) {
            return true;
        }

        // Undangan privat bentuk lama: joinchat/AbCdEfGh
        if (preg_match('/^joinchat\/[A-Za-z0-9_-]{8,}$/', $path) === 1) {
            return true;
        }

        // Grup/channel publik: namagrup  atau  namagrup/12 (topik).
        return preg_match('/^[A-Za-z0-9_]{4,}(\/\d+)?$/', $path) === 1;
    }

    /**
     * Dua entri dengan id sama dianggap satu channel (mis. duplikat karena
     * admin mengisi ulang form).
     *
     * @param array<int, array{id: string, url: string, label: string}> $channels
     * @return array<int, array{id: string, url: string, label: string}>
     */
    private static function dedupe(array $channels): array
    {
        $seen = [];
        $unique = [];

        foreach ($channels as $channel) {
            $key = strtolower($channel['id']);

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $channel;
        }

        return $unique;
    }
}
