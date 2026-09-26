<?php

namespace App\Services\Bot;

use App\Models\BotLocalePreference;
use Illuminate\Support\Facades\App;

/**
 * Resolver bahasa bot.
 *
 * Rantai resolusi (urut prioritas, keputusan 2026-09-26):
 *   1. Preferensi eksplisit tersimpan (locale_source = explicit)  → MENGIKAT
 *   2. Benih auto-deteksi (locale_source = detected)
 *   3. Default panel (setting_webs.bot_default_locale)
 *   4. Literal 'id' — deterministik, BUKAN config('app.locale')
 *      (lihat catatan di defaultLocale(): app.locale dimutasi per-request
 *      oleh LanguageDetectMiddleware dari header Telegram, bukan user)
 *
 * ATURAN MENGIKAT: `seed()` tidak pernah menimpa baris yang sudah ada. Sekali
 * user memilih bahasa (atau sekali benih tertulis), `language_code` Telegram
 * tidak lagi berpengaruh untuk user itu. Ini yang mencegah bug "user pilih
 * Indonesia, pesan berikutnya balik Inggris".
 *
 * Scope: Telegram saja. Adapter WhatsApp tidak memanggil kelas ini.
 */
class BotLocale
{
    /** Bahasa yang benar-benar didukung. Di luar ini → jatuh ke default panel. */
    public const SUPPORTED = ['id', 'en'];

    /** Sumber konteks yang dianggap sebagai chat privat ( boleh di-seed ). */
    private const PRIVATE_CHAT = 'private';

    /**
     * Tentukan bahasa untuk konteks ini. SELALU mengembalikan locale valid.
     *
     * @param  array<string, mixed>  $context
     */
    public function resolve(array $context): string
    {
        $preference = $this->preferenceFor($context);

        if ($preference !== null) {
            $stored = $this->normalize($preference->locale);

            if ($stored !== null) {
                return $stored;
            }
        }

        return $this->defaultLocale();
    }

    /**
     * Benih bahasa dari `language_code` Telegram.
     *
     * Hanya berlaku untuk:
     *  - chat privat (di grup, `language_code` milik pengirim, bukan audiens),
     *  - kode bahasa yang didukung,
     *  - percakapan yang BELUM punya baris preferensi.
     *
     * Kalau tidak ada yang bisa dipakai, tidak ada baris yang ditulis — kita
     * tidak menyimpan tebakan kosong ke database.
     *
     * @param  array<string, mixed>  $context
     */
    public function seed(array $context, ?string $languageCode): void
    {
        if (! $this->isPrivateChat($context)) {
            return;
        }

        $detected = $this->normalize($languageCode);

        if ($detected === null) {
            return;
        }

        $key = $this->keyFor($context);

        if ($key === null) {
            return;
        }

        // Aturan mengikat: jangan sentuh baris yang sudah ada. Memakai
        // firstOrCreate (bukan updateOrCreate) supaya balapan webhook pun
        // tidak bisa menimpa preferensi eksplisit.
        BotLocalePreference::query()->firstOrCreate(
            $key,
            ['locale' => $detected, 'locale_source' => BotLocalePreference::SOURCE_DETECTED],
        );
    }

    /**
     * Simpan pilihan eksplisit user (dari `/bahasa`). Mengikat permanen:
     * setelah ini `seed()` tidak akan pernah mengubahnya lagi.
     *
     * @param  array<string, mixed>  $context
     */
    public function setForContext(array $context, string $locale): void
    {
        $normalized = $this->normalize($locale);

        if ($normalized === null) {
            return;
        }

        $key = $this->keyFor($context);

        if ($key === null) {
            return;
        }

        BotLocalePreference::query()->updateOrCreate(
            $key,
            ['locale' => $normalized, 'locale_source' => BotLocalePreference::SOURCE_EXPLICIT],
        );
    }

    /**
     * 'en-US' / 'en_GB' / 'EN' → 'en'. Kode di luar whitelist ('fr', 'ru') dan
     * nilai kosong → null, supaya tidak ada pesan campur bahasa.
     */
    public function normalize(?string $code): ?string
    {
        if ($code === null || trim($code) === '') {
            return null;
        }

        $base = strtolower(explode('-', str_replace('_', '-', trim($code)))[0]);

        return in_array($base, self::SUPPORTED, true) ? $base : null;
    }

    /**
     * Locale default dari panel admin.
     *
     * Sengaja TIDAK memakai `config('app.locale')` sebagai jaring terakhir:
     * nilai itu dimutasi per-request oleh `LanguageDetectMiddleware` dari header
     * `Accept-Language`. Di webhook bot, header itu berasal dari Telegram —
     * bukan dari user — sehingga hasilnya non-deterministik. Terbukti saat
     * probe runtime: dengan kolom panel berisi nilai tak dikenal, resolusi
     * jatuh ke `en` hanya karena `app.locale` request itu `en`.
     *
     * Jaring terakhir yang deterministik: 'id' (pasar sebenarnya, sama dengan
     * default kolom `setting_webs.bot_default_locale`).
     */
    public function defaultLocale(): string
    {
        return $this->normalize($this->panelDefault()) ?? 'id';
    }

    /**
     * Terapkan locale ke request ini.
     *
     * WAJIB dipanggil dalam try/finally oleh pemanggil: `App::setLocale()`
     * bersifat global per-proses, jadi di worker antrean nilainya bisa bocor
     * ke job berikutnya kalau tidak dipulihkan.
     */
    public function apply(string $locale): void
    {
        $normalized = $this->normalize($locale) ?? $this->defaultLocale();

        App::setLocale($normalized);
    }

    /** Nilai default dari panel — dibaca lewat bridge config (lihat AppServiceProvider). */
    private function panelDefault(): ?string
    {
        $value = config('services.telegram-bot-api.default_locale');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Baris preferensi untuk konteks ini, atau null.
     *
     * @param  array<string, mixed>  $context
     */
    private function preferenceFor(array $context): ?BotLocalePreference
    {
        $key = $this->keyFor($context);

        if ($key === null) {
            return null;
        }

        return BotLocalePreference::query()->where($key)->first();
    }

    /**
     * Kunci baris: `source` + `external_user_id`. Sengaja memakai
     * external_user_id (mis. `telegram:default:6252007210`) supaya sejalan
     * dengan kunci state checkout dan tidak butuh pemetaan kedua.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, string>|null
     */
    private function keyFor(array $context): ?array
    {
        $source = trim((string) ($context['source'] ?? ''));
        $externalUserId = trim((string) ($context['external_user_id'] ?? ''));

        if ($source === '' || $externalUserId === '') {
            return null;
        }

        return [
            'source' => $source,
            'external_user_id' => $externalUserId,
        ];
    }

    /**
     * Hanya chat privat yang boleh di-seed. Konteks tanpa tipe chat yang jelas
     * diperlakukan sebagai NON-privat (fail closed) — sama seperti gerbang lain
     * di bot ini.
     *
     * @param  array<string, mixed>  $context
     */
    private function isPrivateChat(array $context): bool
    {
        return ($context['telegram_chat_type'] ?? null) === self::PRIVATE_CHAT;
    }
}
