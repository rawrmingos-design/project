<?php

namespace App\Services\Bot;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Peta nomor → perintah untuk pemilihan lewat keyboard angka Telegram.
 *
 * **Kenapa store sendiri, bukan memakai mesin yang sudah ada di jalur WA.**
 * `FonnteAdapter`/`OpenWaAdapter` menyimpan peta serupa, tapi terkunci di dalam
 * adapternya (`source = 'whatsapp_gateway'`, kunci `whatsapp:<nomor>`, dan peta
 * itu dirakit ULANG dari tombol pada tiap render). Ekstraksi berarti menyentuh
 * jalur WhatsApp yang sedang melayani produksi, dan yang didapat hanya
 * kerapian. Karena itu Telegram memakai store ini; jalur WA tidak tersentuh.
 *
 * **Kenapa ada masa berlaku.** Keyboard angka bersifat GLOBAL: user bisa
 * mengetuk `3` saat sedang membaca invoice, berjam-jam setelah melihat daftar.
 * Tanpa masa berlaku, angka itu mengeksekusi perintah dari layar lampau — user
 * mengira sedang memilih sesuatu yang dia lihat, padahal nomor itu menunjuk
 * item yang sudah tidak relevan.
 */
class BotNumericMenuStore
{
    /**
     * Umur peta nomor, dalam menit.
     *
     * Sengaja JAUH lebih longgar daripada jalur WhatsApp (15 menit). Di sana
     * angka adalah SATU-SATUNYA cara memilih dari daftar; kalau peta ini
     * kedaluwarsa, user melihat daftar yang tidak bisa dipilih sama sekali.
     * Enam jam cukup untuk sesi belanja normal, sementara keyboard angka tetap
     * berhenti bekerja sebelum bisa menunjuk item yang sudah basi.
     */
    public const TTL_MINUTES = 360;

    /**
     * Nomor maksimum untuk entri `content`.
     *
     * Sama dengan batas jalur WhatsApp supaya arti nomor konsisten, dan cukup
     * untuk ukuran halaman terbesar yang dipakai bot (WhatsApp 15/halaman).
     */
    public const CONTENT_ENTRY_LIMIT = 15;

    /**
     * Versi skema state. State dengan versi lain DITOLAK, bukan ditafsirkan:
     * versi berikutnya bisa mengubah arti sebuah field, dan menafsirkan state
     * lama dengan aturan baru berisiko mengeksekusi perintah yang salah.
     */
    public const SCHEMA_VERSION = 1;

    public const SOURCE = 'telegram_gateway';

    /** Kunci cache untuk satu pengirim. */
    public function key(string $externalUserId): string
    {
        return 'bot:numeric-menu-store:' . hash('sha256', $externalUserId);
    }

    /**
     * Simpan peta nomor untuk satu pengirim.
     *
     * Dipanggil setiap kali layar daftar dirender: melihat menu = state segar,
     * sehingga peta tidak pernah basi selama user benar-benar melihat daftar.
     *
     * @param  array{menu?: string, parent_menu?: string|null, page?: int, entries?: array<string, array<string, string>>}  $numericMenu
     * @return array<string, mixed> State yang tersimpan.
     */
    public function put(string $externalUserId, array $numericMenu, string $renderedText): array
    {
        $createdAt = Carbon::now();

        $state = [
            'schema_version' => self::SCHEMA_VERSION,
            'revision' => Str::random(16),
            'source' => self::SOURCE,
            'menu' => (string) ($numericMenu['menu'] ?? ''),
            'entries' => $this->normalizeEntries($numericMenu['entries'] ?? []),
            'parent_menu' => $numericMenu['parent_menu'] ?? null,
            'page' => max(1, (int) ($numericMenu['page'] ?? 1)),
            'created_at' => $createdAt->toIso8601String(),
            'expires_at' => $createdAt->copy()->addMinutes(self::TTL_MINUTES)->toIso8601String(),
            'rendered_text' => $renderedText,
        ];

        Cache::put($this->key($externalUserId), $state, $createdAt->copy()->addMinutes(self::TTL_MINUTES));

        return $state;
    }

    /**
     * Ambil state yang MASIH berlaku. `null` = tidak ada / kedaluwarsa / rusak.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $externalUserId): ?array
    {
        $state = Cache::get($this->key($externalUserId));

        return $this->valid($state) ? $state : null;
    }

    /**
     * Terjemahkan nomor yang diketuk user menjadi perintah.
     *
     * Tiga kemungkinan hasil, dan ketiganya HARUS dibedakan oleh pemanggil:
     * - `ok`      : nomor sah, `command` berisi perintah siap dieksekusi.
     * - `invalid` : ada peta aktif, tapi nomor tidak ada di dalamnya. Sertakan
     *               `rendered_text` supaya user bisa melihat ulang daftarnya.
     * - `expired` : tidak ada peta aktif (belum pernah buka menu, atau sudah
     *               lewat masa berlaku).
     *
     * @return array{status: string, command?: string, rendered_text?: string}
     */
    public function resolve(string $externalUserId, int $number): array
    {
        $state = $this->get($externalUserId);

        if ($state === null) {
            return ['status' => 'expired'];
        }

        $entry = $state['entries'][(string) $number] ?? null;

        if (! $this->validEntry($number, $entry)) {
            return [
                'status' => 'invalid',
                'rendered_text' => (string) $state['rendered_text'],
            ];
        }

        return ['status' => 'ok', 'command' => (string) $entry['command']];
    }

    /**
     * Buang entri yang tidak bisa dipakai, supaya state tersimpan selalu bersih.
     *
     * Entri rusak dibuang di sini, bukan dibiarkan sampai `valid()` menolak
     * SELURUH state: satu entri cacat tidak boleh membuat seluruh daftar mati.
     *
     * @param  array<string, mixed>  $entries
     * @return array<string, array{type: string, label: string, command: string}>
     */
    private function normalizeEntries(array $entries): array
    {
        $normalized = [];

        foreach ($entries as $number => $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $type = $entry['type'] ?? null;
            $command = $entry['command'] ?? null;

            if (! is_string($type) || ! is_string($command) || trim($command) === '') {
                continue;
            }

            if (! $this->validEntry((int) $number, $entry)) {
                continue;
            }

            $normalized[(string) (int) $number] = [
                'type' => $type,
                'label' => (string) ($entry['label'] ?? ''),
                'command' => $command,
            ];
        }

        return $normalized;
    }

    /**
     * Apakah state boleh dipercaya?
     *
     * Menolak lebih baik daripada menafsirkan: state yang tidak sesuai bentuk
     * yang diharapkan bisa membuat nomor menunjuk perintah yang salah.
     *
     * @param  mixed  $state
     */
    private function valid(mixed $state): bool
    {
        if (
            ! is_array($state)
            || ($state['schema_version'] ?? null) !== self::SCHEMA_VERSION
            || ($state['source'] ?? null) !== self::SOURCE
            || ! is_string($state['revision'] ?? null)
            || preg_match('/^[A-Za-z0-9]{16}$/', (string) $state['revision']) !== 1
            || ! is_array($state['entries'] ?? null)
            || ! is_string($state['rendered_text'] ?? null)
            || ! is_string($state['created_at'] ?? null)
            || ! is_string($state['expires_at'] ?? null)
        ) {
            return false;
        }

        try {
            $createdAt = Carbon::parse($state['created_at']);
            $expiresAt = Carbon::parse($state['expires_at']);
        } catch (\Throwable) {
            return false;
        }

        // Kadaluwarsa diperiksa dari timestamp di dalam state, BUKAN hanya
        // mengandalkan TTL cache: driver cache bisa berbeda antar environment
        // (file vs redis), dan state yang tersimpan lebih lama dari TTL-nya
        // tetap tidak boleh dipakai.
        if ($createdAt->isFuture() || $expiresAt->isPast() || ! $expiresAt->greaterThan($createdAt)) {
            return false;
        }

        foreach ($state['entries'] as $number => $entry) {
            if (
                preg_match('/^(?:0|[1-9]\d*)$/', (string) $number) !== 1
                || ! $this->validEntry((int) $number, $entry)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Bentuk dan nomor yang sah untuk satu entri.
     *
     * 98/99 = pindah halaman, 0 = kembali; sama seperti jalur WhatsApp supaya
     * arti nomor tidak berbeda antar channel.
     */
    private function validEntry(int $number, mixed $entry): bool
    {
        if (
            ! is_array($entry)
            || ! is_string($entry['type'] ?? null)
            || ! is_string($entry['label'] ?? null)
            || ! is_string($entry['command'] ?? null)
            || trim($entry['command']) === ''
        ) {
            return false;
        }

        return match ($entry['type']) {
            'content' => $number >= 1 && $number <= self::CONTENT_ENTRY_LIMIT,
            'navigation_previous' => $number === 98,
            'navigation_next' => $number === 99,
            'back' => $number === 0,
            default => false,
        };
    }
}
