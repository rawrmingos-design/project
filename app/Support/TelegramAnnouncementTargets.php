<?php

namespace App\Support;

/**
 * Sumber tunggal tujuan PENGUMUMAN Telegram (grup forum + topik).
 *
 * Berbeda dari {@see TelegramRequiredChannels} yang menyatakan SYARAT
 * masuk, kelas ini menyatakan TUJUAN kirim untuk admin.
 *
 * Urutan resolusi:
 *   1. `services.telegram-bot-api.announcement_targets` (array) —
 *      diisi dari kolom JSON `setting_webs.telegram_announcement_targets`
 *      lewat AppServiceProvider.
 *   2. Kosong → tidak ada tujuan; command pengumuman berhenti dengan
 *      pesan jelas, bukan mengirim ke tempat yang salah.
 *
 * Bentuk satu entri:
 *   ['label' => 'Announcement', 'chat_id' => '-1001234567890', 'thread_id' => 12]
 *
 * CATATAN `chat_id`: harus id supergrup numerik (mis. `-100…`). Username
 * publik (@nama) DITOLAK untuk tujuan kirim karena bot harus benar-benar
 * anggota grup tersebut, dan id numerik membuat kesalahan kirim ke grup
 * lain jadi tidak mungkin. `thread_id` kosong/0 = kirim ke chat utama
 * (topik "General" bawaan).
 */
final class TelegramAnnouncementTargets
{
    /**
     * @return array<int, array{label: string, chat_id: string, thread_id: int|null}>
     */
    public static function all(): array
    {
        $targets = [];

        foreach ((array) config('services.telegram-bot-api.announcement_targets', []) as $entry) {
            $target = self::normalize(is_array($entry) ? $entry : []);

            if ($target !== null) {
                $targets[] = $target;
            }
        }

        return self::dedupe($targets);
    }

    /**
     * Tujuan yang valid, atau null bila tidak ada (pemanggil harus
     * menangani kasus ini dengan pesan jelas).
     *
     * @return array<int, array{label: string, chat_id: string, thread_id: int|null}>
     */
    public static function allOrEmpty(): array
    {
        return self::all();
    }

    public static function isEmpty(): bool
    {
        return self::all() === [];
    }

    /**
     * @param array<string, mixed> $entry
     * @return array{label: string, chat_id: string, thread_id: int|null}|null
     */
    private static function normalize(array $entry): ?array
    {
        $chatId = trim((string) ($entry['chat_id'] ?? ''));

        // Hanya id numerik yang diterima: grup (-100…) atau id positif.
        // Username publik tidak cukup aman untuk tujuan kirim otomatis.
        if (! preg_match('/^-?\d{5,}$/', $chatId)) {
            return null;
        }

        $threadRaw = $entry['thread_id'] ?? null;
        $threadId = null;

        if ($threadRaw !== null && $threadRaw !== '' && (int) $threadRaw > 0) {
            $threadId = (int) $threadRaw;
        }

        $label = trim((string) ($entry['label'] ?? ''));

        return [
            'label' => $label !== '' ? $label : ($threadId !== null ? 'Topik ' . $threadId : $chatId),
            'chat_id' => $chatId,
            'thread_id' => $threadId,
        ];
    }

    /**
     * Dua entri dengan pasangan chat_id + thread_id sama dianggap satu
     * tujuan (mis. duplikat akibat admin mengisi ulang form).
     *
     * @param array<int, array{label: string, chat_id: string, thread_id: int|null}> $targets
     * @return array<int, array{label: string, chat_id: string, thread_id: int|null}>
     */
    private static function dedupe(array $targets): array
    {
        $seen = [];
        $unique = [];

        foreach ($targets as $target) {
            $key = $target['chat_id'] . ':' . ($target['thread_id'] ?? 'main');

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $target;
        }

        return $unique;
    }
}
