<?php

namespace App\Support;

/**
 * Sumber tunggal daftar channel Telegram yang WAJIB diikuti sebelum user
 * bisa membuka katalog / membuat order.
 *
 * Urutan resolusi:
 *   1. `services.telegram-bot-api.required_channel.channels` (array) —
 *      diisi dari kolom JSON `setting_webs.telegram_required_channels`
 *      lewat AppServiceProvider.
 *   2. Channel tunggal lama (`required_channel.id` + `.url`), tetap
 *      dihormati supaya deployment yang belum pindah tidak berubah
 *      perilaku.
 *
 * Bentuk satu entri: ['id' => '@channel', 'url' => 'https://t.me/channel', 'label' => 'Channel']
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

        if ($channels !== []) {
            return self::dedupe($channels);
        }

        $legacy = self::normalize([
            'id' => (string) config('services.telegram-bot-api.required_channel.id', ''),
            'url' => (string) config('services.telegram-bot-api.required_channel.url', ''),
        ]);

        return $legacy === null ? [] : [$legacy];
    }

    /**
     * Hanya entri yang lolos validasi bentuk channel publik Telegram.
     *
     * @param array<string, mixed> $entry
     * @return array{id: string, url: string, label: string}|null
     */
    private static function normalize(array $entry): ?array
    {
        $id = trim((string) ($entry['id'] ?? ''));
        $url = trim((string) ($entry['url'] ?? ''));

        if (! preg_match('/^@[A-Za-z0-9_]{5,}$/', $id)) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);

        $isValidUrl = strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && strtolower((string) ($parts['host'] ?? '')) === 't.me'
            && trim((string) ($parts['path'] ?? ''), '/') === ltrim($id, '@');

        if (! $isValidUrl) {
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
     * Dua entri dengan id sama dianggap satu channel (mis. duplikat akibat
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
