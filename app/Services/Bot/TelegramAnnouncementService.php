<?php

namespace App\Services\Bot;

use App\Support\TelegramAnnouncementTargets;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mengirim PENGUMUMAN admin ke grup topik Telegram.
 *
 * Mendukung `message_thread_id`, jadi pesan bisa diarahkan tepat ke topik
 * (mis. "Announcement") alih-alih chat utama. Ini yang membuat Announcement
 * dan General tidak saling mengganggu.
 *
 * Bot WAJIB sudah menjadi anggota grup tujuan dan — untuk mengirim ke
 * sebuah topik — sebaiknya admin (atau minimal boleh mengirim pesan).
 */
class TelegramAnnouncementService
{
    /** Batas Telegram untuk satu pesan teks. */
    private const MAX_MESSAGE_LENGTH = 4096;

    public function __construct(
        private readonly BotMessageFormatter $formatter = new BotMessageFormatter(),
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->token() !== '' && ! TelegramAnnouncementTargets::isEmpty();
    }

    /**
     * @return array<int, array{label: string, ok: bool, error: string|null}>
     */
    public function send(string $text, bool $markdown = true): array
    {
        $token = $this->token();

        if ($token === '') {
            return [[
                'label' => '(konfigurasi)',
                'ok' => false,
                'error' => 'Bot token Telegram belum dikonfigurasi.',
            ]];
        }

        $text = trim($text);

        if ($text === '') {
            return [[
                'label' => '(input)',
                'ok' => false,
                'error' => 'Isi pengumuman kosong.',
            ]];
        }

        if (mb_strlen($text) > self::MAX_MESSAGE_LENGTH) {
            return [[
                'label' => '(input)',
                'ok' => false,
                'error' => 'Isi pengumuman melebihi ' . self::MAX_MESSAGE_LENGTH . ' karakter.',
            ]];
        }

        $targets = TelegramAnnouncementTargets::all();

        if ($targets === []) {
            return [[
                'label' => '(konfigurasi)',
                'ok' => false,
                'error' => 'Belum ada tujuan pengumuman. Isi daftar "Tujuan Pengumuman" di Settings → Notifications.',
            ]];
        }

        $results = [];

        foreach ($targets as $target) {
            $results[] = $this->sendToTarget($token, $target, $text, $markdown);
        }

        return $results;
    }

    /**
     * @param array{label: string, chat_id: string, thread_id: int|null} $target
     * @return array{label: string, ok: bool, error: string|null}
     */
    private function sendToTarget(string $token, array $target, string $text, bool $markdown): array
    {
        $payload = [
            'chat_id' => $target['chat_id'],
            'text' => $text,
        ];

        if ($markdown) {
            $payload['parse_mode'] = 'Markdown';
        }

        if ($target['thread_id'] !== null) {
            $payload['message_thread_id'] = $target['thread_id'];
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            if ($response->successful() && $response->json('ok') === true) {
                return ['label' => $target['label'], 'ok' => true, 'error' => null];
            }

            $description = (string) ($response->json('description') ?? 'HTTP ' . $response->status());
            $hint = $this->hintFor($description);

            Log::warning('Telegram announcement failed.', [
                'target' => $target['label'],
                'thread_id' => $target['thread_id'],
                'description' => $description,
            ]);

            return ['label' => $target['label'], 'ok' => false, 'error' => $hint ?? $description];
        } catch (\Throwable $e) {
            Log::warning('Telegram announcement threw.', [
                'target' => $target['label'],
                'message' => $e->getMessage(),
            ]);

            return ['label' => $target['label'], 'ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function token(): string
    {
        return trim((string) config('services.telegram-bot-api.token'));
    }

    /**
     * Terjemahkan error Telegram yang paling sering muncul menjadi
     * kalimat yang bisa langsung ditindak admin.
     *
     * `TOPIC_CLOSED` itu contoh nyata: topik Announcement sengaja ditutup
     * supaya member tidak bisa posting, dan Telegram menolak kiriman dari
     * bot yang belum admin grup. Pesan aslinya tidak menjelaskan itu,
     * jadi admin akan bingung. Ini yang menerjemahkannya.
     *
     * Pesan asli tetap ditampilkan di belakang supaya tidak ada informasi
     * yang hilang saat menelusuri masalah.
     */
    private function hintFor(string $description): ?string
    {
        $lower = strtolower($description);

        if (str_contains($lower, 'topic_closed')) {
            return 'Topik tujuan sedang DITUTUP. Telegram hanya mengizinkan admin grup posting '
                . 'ke topik tertutup — jadikan bot admin grup (centang "Manage Topics"), '
                . 'atau pakai topik yang terbuka. [' . $description . ']';
        }

        if (str_contains($lower, 'not enough rights') || str_contains($lower, 'chat_write_forbidden')) {
            return 'Bot tidak punya hak menulis di grup/topik itu. Jadikan bot admin grup '
                . 'dengan hak "Manage Topics" dan "Send Messages". [' . $description . ']';
        }

        if (str_contains($lower, 'thread not found')) {
            return 'Thread ID topik tidak ditemukan. Cek kembali Thread ID tujuan; '
                . 'topik "General" adalah chat utama sehingga TIDAK butuh Thread ID '
                . '(kosongkan saja). [' . $description . ']';
        }

        if (str_contains($lower, 'chat not found')) {
            return 'Bot belum menjadi anggota grup itu, atau Chat ID salah. '
                . 'Tambahkan bot ke grup dulu. [' . $description . ']';
        }

        return null;
    }
}
