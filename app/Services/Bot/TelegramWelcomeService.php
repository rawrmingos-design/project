<?php

namespace App\Services\Bot;

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

        // Nilai dari Telegram di-escape agar nama seperti "Budi_Pratama"
        // tidak merusak format Markdown (underscore = italic).
        $text = str_replace(
            ['{nama}', '{grup}', '{sebutan}'],
            [
                $this->escape($name),
                $this->escape((string) ($chat['title'] ?? '') ?: 'grup kami'),
                $this->escape($name),
            ],
            $template,
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
            'parse_mode' => 'Markdown',
            // Jangan tampilkan preview tautan untuk pesan sambutan.
            'link_preview_options' => ['is_disabled' => true],
        ];

        $threadId = $this->threadId();

        if ($threadId !== null) {
            $payload['message_thread_id'] = $threadId;
        }

        try {
            $response = Http::timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);

            if ($response->successful() && $response->json('ok') === true) {
                return ['ok' => true, 'error' => null];
            }

            $description = (string) ($response->json('description') ?? 'HTTP ' . $response->status());

            Log::warning('Telegram welcome failed.', [
                'chat_id' => $chatId,
                'error' => $description,
            ]);

            return ['ok' => false, 'error' => $description];
        } catch (\Throwable $e) {
            Log::warning('Telegram welcome threw.', [
                'chat_id' => $chatId,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'error' => $e->getMessage()];
        }
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
    private function escape(string $value): string
    {
        return str_replace(
            ['\\', '_', '*', '[', ']', '`'],
            ['\\\\', '\\_', '\\*', '\\[', '\\]', '\\`'],
            $value,
        );
    }
}
