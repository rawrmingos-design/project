<?php

namespace App\Services\Settings;

use App\Models\SettingWeb;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Jembatan setelan panel (tabel `setting_webs`) -> config runtime.
 *
 * SEBELUMNYA logika ini hanya hidup di dalam `AppServiceProvider::boot()` dan
 * dibungkus `if (! app()->runningInConsole())`. Akibatnya ia HANYA jalan pada
 * request web. Begitu pemrosesan bot dipindah ke antrean (queue worker =
 * proses console), jembatan itu mati - dan yang mati bersamanya adalah
 * setelan yang justru paling penting:
 *
 *   - `telegram_bot_token`  -> tanpa ini bot tidak bisa membalas sama sekali
 *   - `telegram_webhook_secret` -> tanpa ini endpoint menolak SEMUA request (401)
 *   - `telegram_required_channels` (daftar grup wajib, SATU sumbernya panel)
 *   - `bot_order_tg_enabled` / `bot_order_wa_enabled`
 *
 * Karena itu logikanya dipindah ke sini dan dipanggil dari DUA tempat: satu
 * middleware pada route webhook (jalur web, mempertahankan perilaku lama), dan
 * sekali di dalam job bot (jalur worker). `guard` mencegah penerapan ganda
 * dalam satu proses.
 */
class DatabaseSettingsBridge
{
    private const GUARD_KEY = 'bot.database_settings_applied';

    /**
     * Terapkan setelan DB ke config. Idempoten: panggilan kedua dalam proses
     * yang sama tidak melakukan apa-apa.
     *
     * @param  array<string, mixed>  $defaults  nilai dasar saat kolom DB kosong
     * @return object hasil gabungan yang dipakai provider untuk View::share
     */
    public function apply(array $defaults = []): object
    {
        $merged = (object) $defaults;

        if (config(self::GUARD_KEY) === true) {
            return $merged;
        }

        config([self::GUARD_KEY => true]);

        try {
            $dbConfig = DB::table('setting_webs')->where('id', 1)->first();

            if (! $dbConfig) {
                return $merged;
            }

            $dbConfig = (array) $dbConfig;
            unset($dbConfig['tiktok_access_token_encrypted']);
            $merged = (object) array_merge($defaults, $dbConfig);

            $this->applyMailAndCaptcha($merged);
            $this->applyTelegram($merged);
            $this->applyOrderFlags($merged);
            $this->applyWhatsappChannels($merged);
        } catch (\Throwable $e) {
            // DB tidak tersedia: pakai default, jangan jatuhkan proses.
            Log::warning('DatabaseSettingsBridge: DB config load failed, using defaults.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }

        return $merged;
    }

    private function applyMailAndCaptcha(object $config): void
    {
        config([
            'mail.default' => $config->mail_mailer ?: env('MAIL_MAILER', 'smtp'),
            'mail.mailers.smtp.host' => $config->mail_host ?: env('MAIL_HOST', 'smtp.mailgun.org'),
            'mail.mailers.smtp.port' => $config->mail_port ?: env('MAIL_PORT', 587),
            'mail.mailers.smtp.encryption' => $config->mail_encryption ?: env('MAIL_ENCRYPTION', 'tls'),
            'mail.mailers.smtp.username' => $config->mail_username ?: env('MAIL_USERNAME'),
            'mail.mailers.smtp.password' => $config->mail_password ?: env('MAIL_PASSWORD'),
            'mail.from.address' => $config->mail_from_address ?: env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'mail.from.name' => $config->mail_from_name ?: env('MAIL_FROM_NAME', 'Example'),
            'captcha.sitekey' => $config->captcha_site_key ?: env('NOCAPTCHA_SITEKEY'),
            'captcha.secret' => $config->captcha_secret ?: env('NOCAPTCHA_SECRET'),
        ]);
    }

    private function applyTelegram(object $config): void
    {
        if (! empty($config->telegram_bot_token)) {
            config(['services.telegram-bot-api.token' => $config->telegram_bot_token]);
        }

        // Secret ini yang dipakai endpoint webhook untuk mengautentikasi
        // Telegram. Kalau jembatan ini tidak jalan di worker, nilainya kosong
        // dan setiap request ditolak 401 - kegagalan yang tampak seperti
        // "bot mati" padahal cuma setelan yang tidak terbaca.
        if (! empty($config->telegram_webhook_secret)) {
            config(['services.telegram-bot-api.webhook_secret' => $config->telegram_webhook_secret]);
        }

        // Daftar grup/channel wajib - SATU sumber: kolom JSON
        // `setting_webs.telegram_required_channels` dari panel. Tidak ada
        // fallback .env: dulu dua sumber ini membuat admin bingung karena
        // isian panel diabaikan.
        $requiredChannels = $config->telegram_required_channels ?? null;

        if (is_string($requiredChannels) && $requiredChannels !== '') {
            $requiredChannels = json_decode($requiredChannels, true);
        }

        if (is_array($requiredChannels) && $requiredChannels !== []) {
            config(['services.telegram-bot-api.required_channel.channels' => array_values($requiredChannels)]);
        }

        // URL kontak admin Telegram - nilai DB menang atas .env; .env tetap
        // dipakai kalau kolom ini kosong supaya deployment lama tidak berubah.
        if (! empty($config->telegram_admin_url)) {
            config(['services.telegram-bot-api.admin_contact_url' => $config->telegram_admin_url]);
        }

        // Default bahasa bot. Diisi APA ADANYA kalau tidak kosong; BotLocale
        // yang memvalidasi lewat whitelist id/en.
        if (! empty($config->bot_default_locale)) {
            config(['services.telegram-bot-api.default_locale' => $config->bot_default_locale]);
        }

        // Sambutan otomatis member baru di grup Telegram. Saklar dihormati apa
        // adanya (termasuk `false`) supaya admin bisa mematikan dari DB.
        if ($config->telegram_welcome_enabled !== null) {
            config(['services.telegram-bot-api.telegram_welcome_enabled' => (bool) $config->telegram_welcome_enabled]);
        }

        if (! empty($config->telegram_welcome_template)) {
            config(['services.telegram-bot-api.telegram_welcome_template' => $config->telegram_welcome_template]);
        }

        if (! empty($config->telegram_welcome_thread_id)) {
            config(['services.telegram-bot-api.telegram_welcome_thread_id' => (int) $config->telegram_welcome_thread_id]);
        }
    }

    private function applyOrderFlags(object $config): void
    {
        // NOTE: baca nilai config-cache DULU - baris berikutnya menimpa
        // `order_enabled` dari DB. Saat config:cache aktif, env() di runtime
        // mengembalikan null, jadi nilai asli dibaca dari config.
        $orderEnabled = (bool) config('services.telegram-bot-api.order_enabled', false);

        config(['services.telegram-bot-api.order_enabled' => (bool) $config->bot_order_tg_enabled]);
        config(['bot.order_wa_enabled' => (bool) $config->bot_order_wa_enabled]);
        config(['bot.order_enabled' => $orderEnabled]);
    }

    private function applyWhatsappChannels(object $config): void
    {
        config([
            'bot.use_separate_bot_wa' => (bool) ($config->use_separate_bot_wa ?? false),
            'bot.wa_bot_key' => $config->wa_bot_key ?? null,
            'bot.wa_bot_number' => $config->wa_bot_number ?? null,
            'bot.openwa_session_id' => $config->openwa_session_id ?? null,
            'bot.openwa_webhook_secret' => $config->openwa_webhook_secret ?? null,
        ]);
    }
}
