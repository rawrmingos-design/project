<?php

namespace App\Services\Bot;

use App\Support\TelegramAnnouncementTargets;
use App\Support\TelegramMarkdown;
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
     * Kirim ke satu tujuan.
     *
     * Mode format yang dipakai adalah **MarkdownV2**, bukan Markdown lama.
     * Alasannya dibuktikan dengan uji langsung ke API Telegram:
     *
     *   parse_mode   teks masuk                        hasil
     *   ----------   -------------------------------   ---------------------
     *   Markdown     *tebal* _miring_ @jasakoding_bot  GAGAL (can't parse entities)
     *   Markdown     **tebal** __miring__              terkirim, TIDAK ada format
     *   MarkdownV2   *tebal* _miring_                  bold + italic  ✓
     *   MarkdownV2   **tebal** __miring__              bold + underline  ✓
     *   MarkdownV2   **tebal** @jasakoding\_bot        bold + mention  ✓
     *
     * Ringkasnya: sintaks `**tebal**` dan `__garis__` HANYA berfungsi di
     * MarkdownV2. Di Markdown lama keduanya cuma tampil mentah.
     *
     * MarkdownV2 mewajibkan SEMUA karakter spesial di-escape, jadi teks
     * admin di-escape dulu lewat App\Support\TelegramMarkdown. Efeknya:
     *  - `@jasakoding_bot` tampil utuh dan tetap jadi sebutan yang bisa
     *    diklik (sebelumnya justru teks biasa, atau malah bikin gagal).
     *  - Admin tetap bisa memakai `*tebal*`, `_miring_`, `**tebal**`,
     *    `__garis bawah__`, `~coret~` karena sengaja dibuka kembali.
     *
     * @param array{label: string, chat_id: string, thread_id: int|null} $target
     * @return array{label: string, ok: bool, error: string|null}
     */
    private function sendToTarget(string $token, array $target, string $text, bool $markdown): array
    {
        $payload = [
            'chat_id' => $target['chat_id'],
            'text' => $markdown ? TelegramMarkdown::format($text) : $text,
        ];

        if ($markdown) {
            $payload['parse_mode'] = 'MarkdownV2';
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

            // BUG NYATA: pesan yang tampak biasa tetap bisa ditolak Telegram
            // karena ketidakcocokan Markdown. Contoh yang benar-benar terjadi:
            // admin menulis "@jasakoding_bot" — garis bawahnya dibaca sebagai
            // pembuka italic, sehingga Telegram menjawab "can't parse entities"
            // dan pengumuman GAGAL TOTAL terkirim.
            //
            // Pengumuman yang sampai ke member (tanpa format) jauh lebih
            // berguna daripada pengumuman yang hilang karena satu karakter.
            // Jadi kalau yang salah cuma formatnya, kirim ulang tanpa
            // parse_mode. Kalau masih gagal, baru laporkan error aslinya.
            if ($markdown && $this->isParseError($description)) {
                Log::warning('Telegram announcement gagal parsing Markdown, kirim ulang tanpa format.', [
                    'target' => $target['label'],
                    'thread_id' => $target['thread_id'],
                    'description' => $description,
                ]);

                unset($payload['parse_mode']);

                $retry = Http::timeout(15)
                    ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

                if ($retry->successful() && $retry->json('ok') === true) {
                    return ['label' => $target['label'], 'ok' => true, 'error' => null];
                }

                $description = (string) ($retry->json('description') ?? 'HTTP ' . $retry->status());
            }

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
     * Apakah error ini soal format Markdown, bukan soal hak akses/topik?
     *
     * Frase nyata dari Telegram: "can't parse entities: Can't find end of
     * the entity starting at byte offset N". Dicek longgar supaya tidak
     * rapuh terhadap perubahan kalimat.
     */
    private function isParseError(string $description): bool
    {
        $lower = strtolower($description);

        return str_contains($lower, 'parse entities')
            || str_contains($lower, 'can\'t find end of the entity')
            || str_contains($lower, 'unsupported start tag');
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

        if ($this->isParseError($description)) {
            return 'Format pesan ditolak Telegram, dan pengiriman ulang tanpa format juga gagal. '
                . 'Penyebab tersering: penanda format tidak berpasangan — misalnya satu tanda '
                . 'bintang saja (*teks tanpa penutup). Untuk tebal pakai *teks*, miring _teks_, '
                . 'garis bawah __teks__; pastikan selalu berpasangan. '
                . '[' . $description . ']';
        }

        return null;
    }
}
