<?php

namespace App\Services\Bot;

use App\Support\TelegramMarkdown;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sambutan otomatis untuk member BARU di grup Telegram.
 *
 * Dipanggil dari webhook saat update `message.new_chat_members` diterima.
 * `new_chat_members` adalah SERVICE MESSAGE, dan menurut FAQ resmi Telegram
 * SEMUA bot menerimanya "regardless of settings" — jadi TIDAK perlu
 * mematikan privacy mode, dan TIDAK perlu bot jadi admin, hanya untuk
 * menyapa member baru. (Berbeda dengan update `chat_member` yang butuh
 * admin + `allowed_updates` eksplisit.)
 *
 * Catatan penting: Telegram menghapus service message "user X bergabung"
 * di grup SANGAT besar, sehingga `new_chat_members` tidak selalu datang
 * di sana. Untuk grup biasa, jalur ini andal.
 */
class TelegramWelcomeService
{
    /** Batas Telegram untuk satu pesan teks. */
    private const MAX_MESSAGE_LENGTH = 4096;

    /** Nilai default bila admin belum mengisi template. */
    public const DEFAULT_TEMPLATE = "Halo {nama}, selamat datang di {grup}! 👋\n\n"
        . "Silakan baca panduan di topik *Announcement*, dan gunakan topik *General* untuk ngobrol atau bertanya.";

    public function isEnabled(): bool
    {
        return (bool) $this->setting('telegram_welcome_enabled', false);
    }

    /**
     * Susun isi sambutan. Dipisah dari pengiriman supaya bisa diuji tanpa
     * memanggil Telegram, dan supaya penolakan (mis. nama kosong) terlihat
     * di test — bukan diam-diam mengirim "Halo ,".
     *
     * @param array{first_name?: string|null, last_name?: string|null, username?: string|null, is_bot?: bool} $member
     * @param array{id?: int|string|null, title?: string|null} $chat
     * @return array{ok: bool, text: string, error: string|null}
     */
    public function resolveText(array $member, array $chat): array
    {
        if (($member['is_bot'] ?? false) === true) {
            return ['ok' => false, 'text' => '', 'error' => 'Member adalah bot.'];
        }

        $name = $this->displayName($member);

        if ($name === '') {
            return ['ok' => false, 'text' => '', 'error' => 'Nama member kosong.'];
        }

        $template = trim((string) ($this->setting('telegram_welcome_template') ?? ''));

        if ($template === '') {
            $template = self::DEFAULT_TEMPLATE;
        }

        // Urutan pemrosesan penting:
        //  1. Nilai yang berasal dari Telegram (nama member, judul grup)
        //     diganti placeholder dulu. Kalau langsung dimasukkan, proses
        //     escape di langkah 2 akan meng-escape ulang dan nama
        //     "Budi_Pratama" muncul sebagai "Budi\_Pratama" di grup.
        //  2. Baru teks template diformat: bagian statis di-escape, penanda
        //     format yang sengaja ditulis admin tetap dibuka.
        //  3. Placeholder diganti nilai aslinya, di-escape penuh karena
        //     nama member tidak boleh menambah format apa pun.
        $text = str_replace(
            ['{nama}', '{grup}', '{sebutan}'],
            ["\u{E010}", "\u{E011}", "\u{E010}"],
            $template,
        );

        $text = TelegramMarkdown::format($text);

        $text = str_replace(
            ["\u{E010}", "\u{E011}"],
            [
                TelegramMarkdown::escapeAll($name),
                TelegramMarkdown::escapeAll((string) ($chat['title'] ?? '') ?: 'grup kami'),
            ],
            $text,
        );

        if (mb_strlen($text) > self::MAX_MESSAGE_LENGTH) {
            return ['ok' => false, 'text' => '', 'error' => 'Isi sambutan melebihi ' . self::MAX_MESSAGE_LENGTH . ' karakter.'];
        }

        return ['ok' => true, 'text' => $text, 'error' => null];
    }

    /**
     * Kirim sambutan untuk SATU member yang baru bergabung.
     *
     * @param array{first_name?: string|null, last_name?: string|null, username?: string|null, is_bot?: bool} $member
     * @param array{id?: int|string|null, title?: string|null} $chat
     * @return array{ok: bool, error: string|null}
     */
    public function greet(array $member, array $chat): array
    {
        if (! $this->isEnabled()) {
            return ['ok' => false, 'error' => 'Sambutan otomatis dimatikan.'];
        }

        $token = trim((string) config('services.telegram-bot-api.token'));

        if ($token === '') {
            return ['ok' => false, 'error' => 'Bot token Telegram belum dikonfigurasi.'];
        }

        $chatId = $chat['id'] ?? null;

        if ($chatId === null || $chatId === '') {
            return ['ok' => false, 'error' => 'Chat ID grup tidak ada di update.'];
        }

        $resolved = $this->resolveText($member, $chat);

        if (! $resolved['ok']) {
            return ['ok' => false, 'error' => $resolved['error']];
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $resolved['text'],
            'parse_mode' => 'MarkdownV2',
            // Jangan tampilkan preview tautan untuk pesan sambutan.
            'link_preview_options' => ['is_disabled' => true],
        ];

        $threadId = $this->threadId();

        if ($threadId !== null) {
            $payload['message_thread_id'] = $threadId;
        }

        try {
            $first = $this->send($token, $payload);

            if ($first['ok']) {
                return ['ok' => true, 'error' => null];
            }

            // Kalau thread id tidak dikenal Telegram, kirim ulang TANPA
            // thread id. Dua sebab nyata yang sudah dibuktikan di runtime:
            //
            //  1. Topik "General" adalah chat utama grup. Mengirim dengan
            //     message_thread_id = id General ditolak "message thread
            //     not found" — memang harus tanpa thread id.
            //  2. Topik tujuan sudah dihapus / id salah tulis admin.
            //
            // Sambutan yang mendarat di chat utama jauh lebih berguna
            // daripada hilang tanpa jejak, jadi fallback ini disengaja.
            if ($threadId !== null && $this->isUnknownThread($first['error'])) {
                Log::warning('Telegram welcome thread tidak valid, kirim ulang ke chat utama.', [
                    'chat_id' => $chatId,
                    'thread_id' => $threadId,
                    'error' => $first['error'],
                ]);

                unset($payload['message_thread_id']);

                $second = $this->send($token, $payload);

                if ($second['ok']) {
                    return ['ok' => true, 'error' => null];
                }

                return ['ok' => false, 'error' => $second['error']];
            }

            return ['ok' => false, 'error' => $first['error']];
        } catch (\Throwable $e) {
            Log::warning('Telegram welcome threw.', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Kirim satu payload ke sendMessage.
     *
     * @param array<string, mixed> $payload
     * @return array{ok: bool, error: string|null}
     */
    private function send(string $token, array $payload): array
    {
        $response = Http::timeout(15)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

        if ($response->successful() && $response->json('ok') === true) {
            return ['ok' => true, 'error' => null];
        }

        $description = (string) ($response->json('description') ?? 'HTTP ' . $response->status());

        Log::warning('Telegram welcome failed.', [
            'chat_id' => $payload['chat_id'] ?? null,
            'thread_id' => $payload['message_thread_id'] ?? null,
            'error' => $description,
        ]);

        return ['ok' => false, 'error' => $description];
    }

    /**
     * Apakah error menunjukkan thread id tidak dikenal Telegram?
     *
     * Frase yang dipakai Telegram: "message thread not found". Dicek
     * longgar (case-insensitive, "thread not found") supaya tidak rapuh
     * terhadap perubahan kecil pada kalimatnya.
     */
    private function isUnknownThread(?string $error): bool
    {
        if ($error === null) {
            return false;
        }

        return str_contains(strtolower($error), 'thread not found');
    }

    /**
     * Sapaan untuk daftar member baru. Bot sendiri DILEWATI — saat bot
     * diundang, `new_chat_members` juga memuat bot, dan menyapa diri
     * sendiri itu memalukan.
     *
     * @param array<int, array<string, mixed>> $members
     * @param array{id?: int|string|null, title?: string|null} $chat
     * @return array<int, array{ok: bool, error: string|null}>
     */
    public function greetMany(array $members, array $chat): array
    {
        $results = [];

        foreach ($members as $member) {
            $results[] = $this->greet(is_array($member) ? $member : [], $chat);
        }

        return $results;
    }

    /**
     * @param array{first_name?: string|null, last_name?: string|null, username?: string|null} $member
     */
    public function displayName(array $member): string
    {
        $full = trim(trim((string) ($member['first_name'] ?? '')) . ' ' . trim((string) ($member['last_name'] ?? '')));

        if ($full !== '') {
            return $full;
        }

        $username = trim((string) ($member['username'] ?? ''));

        return $username !== '' ? '@' . ltrim($username, '@') : '';
    }

    /**
     * Topik tujuan sambutan, atau null untuk chat utama (Topik General).
     */
    public function threadId(): ?int
    {
        $raw = $this->setting('telegram_welcome_thread_id');

        if ($raw === null || $raw === '' || (int) $raw <= 0) {
            return null;
        }

        return (int) $raw;
    }

    /**
     * Baca dari kolom setting_webs (lewat config yang di-bridge
     * AppServiceProvider), dengan fallback ke config agar bisa diuji
     * tanpa DB.
     */
    private function setting(string $key, mixed $default = null): mixed
    {
        $value = config('services.telegram-bot-api.' . $key);

        if ($value !== null) {
            return $value;
        }

        return config('bot.telegram.' . $key, $default);
    }

    /**
     * Escape karakter khusus Markdown ala Telegram untuk nilai yang
     * berasal dari pengguna (nama/grup).
     */

}
