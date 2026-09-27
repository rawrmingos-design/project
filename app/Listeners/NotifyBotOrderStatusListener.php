<?php

namespace App\Listeners;

use App\Events\InvoiceStatusUpdated;
use App\Models\BotLocalePreference;
use App\Models\Pembelian;
use App\Services\Bot\BotGatewayCapabilities;
use App\Services\Bot\BotMessageFormatter;
use App\Support\TelegramIdentity;
use App\Support\TelegramMarkdown;
use App\Services\WhatsappNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyBotOrderStatusListener implements ShouldQueue
{
    /**
     * Bahasa yang dipakai kalau user tidak punya preferensi tersimpan.
     *
     * Konstanta, bukan `config('app.locale')` maupun
     * `setting_webs.bot_default_locale`: di worker antrean `app.locale` bisa
     * berisi sisa locale job sebelumnya, dan default panel bisa berubah tanpa
     * sepengetahuan user yang sudah bertransaksi. Notifikasi jalur uang
     * memakai jaring yang deterministik.
     */
    private const DEFAULT_LOCALE = 'id';

    /**
     * Transisi yang wajib di-notifikasi ke pembeli via bot:
     *  - Payment Lunas (order masih diproses)
     *  - Order Sukses (provider balas sukses)
     *  - Order Gagal/Batal (provider balas gagal)
     *
     * BAHASA (Fase 7): ikut bahasa user — TAPI HANYA dari preferensi yang
     * TERSIMPAN, tidak pernah dari `language_code` mentah.
     *
     * Perbedaan itu seluruh intinya. Notifikasi ini jalan di QUEUE, tanpa
     * konteks request; tidak ada `language_code` yang bisa dibaca, dan
     * `app()->getLocale()` di worker berisi sisa locale job sebelumnya. Yang
     * tersedia hanyalah baris `bot_locale_preferences` — hasil keputusan user
     * sendiri (atau benih auto-deteksi Telegram), yang ditulis saat ia
     * berinteraksi dengan bot.
     *
     * Jadi pembacaannya deterministik: baris ada → pakai; tidak ada → Indonesia.
     * Tidak ada tebakan, tidak ada bahasa yang berubah sendiri di jalur uang.
     *
     * Kalau preferensi user Indonesia sedangkan locale worker kebetulan `en`,
     * hasilnya TETAP Indonesia — locale di-set eksplisit dari baris itu, bukan
     * diwarisi dari proses.
     *
     * Sebelumnya jalur ini SELALU Indonesia (keputusan sadar waktu itu, karena
     * satu-satunya alternatif yang terbayang adalah membaca perangkat). Setelah
     * preferensi tersimpan ada, "selalu Indonesia" justru berarti mengabaikan
     * pilihan eksplisit user.
     *
     * WhatsApp TIDAK ikut: `notifyWhatsapp()` tetap literal Indonesia, dan
     * bagian itu dikunci test `test_whatsapp_orders_do_not_hit_telegram_api`.
     */
    public function handle(InvoiceStatusUpdated $event): void
    {
        $orderId = (string) ($event->payload['order_id'] ?? '');

        if ($orderId === '') {
            return;
        }

        $purchase = Pembelian::query()
            ->with('pembayaran')
            ->where('order_id', $orderId)
            ->first();

        if (! $purchase || ! $purchase->pembayaran) {
            return;
        }

        // Hanya bot order gateway (WA/Telegram) yang dapat notifikasi.
        if (! in_array($purchase->traffic_source, ['whatsapp_gateway', 'telegram_gateway'], true)) {
            return;
        }

        $paymentStatus = strtolower(trim((string) $purchase->pembayaran->status));
        $orderStatus = strtolower(trim((string) $purchase->status));

        // 1) Belum Lunas → tidak ada yang perlu di-notify.
        if (! in_array($paymentStatus, ['lunas', 'paid', 'success', 'sukses'], true)) {
            return;
        }

        // 2) Tentukan jenis notifikasi.
        $isSuccess = in_array($orderStatus, ['sukses', 'success', 'completed', 'complete'], true);
        $isFailed = in_array($orderStatus, ['gagal', 'failed', 'batal', 'cancelled', 'canceled'], true);

        // Hanya `transition` yang dipakai: label yang dilihat user datang dari
        // `formatStatus()` atas `payload.status` (payload di bawah), bukan dari
        // sini. Versi lama juga meng-assign `$summary` yang TIDAK PERNAH dibaca
        // — dead code yang menyesatkan pembaca berikutnya (hutang D6, Fase 4).
        if ($isSuccess) {
            $transition = 'success';
        } elseif ($isFailed) {
            $transition = 'failed';
        } else {
            $transition = 'paid';
        }

        // 3) Anti-spam: event bisa dispatch berulang (callback berulang/poller).
        //    Cache hanya di-set SETELAH kirim sukses — kalau gagal, event berikutnya retry.
        $cacheKey = 'bot:notif:' . $orderId . ':' . $transition;
        if (Cache::has($cacheKey)) {
            return;
        }

        $payload = [
            'ok' => true,
            'data' => [
                'order_id' => (string) $purchase->order_id,
                'product' => (string) $purchase->layanan,
                'nickname' => (string) $purchase->nickname,
                'amount' => (int) $purchase->harga,
                'status' => (string) $purchase->status,
                'payment' => ['status' => (string) $purchase->pembayaran->status],
                'sn' => (string) ($purchase->keterangan_sn ?? ''),
            ],
        ];

        try {
            if ($purchase->traffic_source === 'whatsapp_gateway') {
                $this->notifyWhatsapp($purchase, $orderId, $transition, $cacheKey, $payload);
            }
        } catch (\Throwable $e) {
            Log::error('NotifyBotOrderStatus: exception kirim notif', [
                'order_id' => $orderId,
                'transition' => $transition,
                'error' => $e->getMessage(),
            ]);
        }

        $this->notifyTelegram($purchase, $orderId, $transition, $cacheKey, $payload);
    }

    private function notifyWhatsapp(Pembelian $purchase, string $orderId, string $transition, string $cacheKey, array $payload): void
    {
        $senderDigits = preg_replace('/\D+/', '', (string) $purchase->pembayaran->no_pembeli);
        if ($senderDigits === '') {
            Log::warning('NotifyBotOrderStatus: no_pembeli kosong, tidak bisa kirim notif', ['order_id' => $orderId]);

            return;
        }

        $target = $senderDigits . '@s.whatsapp.net';

        // Worker queue = console context → AppServiceProvider TIDAK set config('bot.*')
        // (boot() di-skip saat runningInConsole). Baca langsung dari DB biar
        // self-contained dan tidak bergantung pada web-runtime config.
        $setting = \App\Models\SettingWeb::first();
        $customToken = ($setting && $setting->use_separate_bot_wa)
            ? (string) $setting->wa_bot_key
            : null;

        $response = app(WhatsappNotificationService::class)
            ->sendMessage($target, app(BotMessageFormatter::class)->formatStatus($payload)['text'], null, $customToken);

        if (! ($response['success'] ?? false)) {
            Log::warning('NotifyBotOrderStatus: gagal kirim notif', [
                'order_id' => $orderId,
                'transition' => $transition,
                'response' => $response,
            ]);
        } else {
            Cache::put($cacheKey, true, now()->addHours(24));
        }
    }

    /**
     * Notifikasi proaktif untuk order Telegram. Identitas penerima
     * adalah gateway_principal (telegram:<id>) — chat_id bot pribadi
     * bernilai sama dengan user ID. Anti-spam memakai cache key yang
     * sama dengan jalur WA supaya satu transisi hanya dikirim sekali
     * lintas channel.
     */
    private function notifyTelegram(Pembelian $purchase, string $orderId, string $transition, string $cacheKey, array $payload): void
    {
        if ($purchase->traffic_source !== 'telegram_gateway') {
            return;
        }

        // Worker queue = console context → AppServiceProvider TIDAK set
        // config('services.telegram-bot-api.token'). Baca langsung dari DB.
        $token = optional(\App\Models\SettingWeb::first())->telegram_bot_token;

        if (! $token) {
            Log::warning('NotifyBotOrderStatus: token Telegram kosong, notif dilewati', ['order_id' => $orderId]);

            return;
        }

        if (! Cache::has($cacheKey)) {
            // Principal kanonik `telegram:<id>`; order lama (sebelum kolom
            // principal ada, atau sesudah regresi format ber-scope) punya
            // principal NULL — fallback ke email legacy `telegram:<id>@telegram.user`.
            $chatId = TelegramIdentity::chatId($purchase->gateway_principal)
                ?? TelegramIdentity::chatId($purchase->email_pembeli);

            if ($chatId === null) {
                Log::warning('NotifyBotOrderStatus: identitas Telegram tidak resolvable, notif dilewati', [
                    'order_id' => $orderId,
                ]);

                return;
            }

            try {
                // Bahasa: HANYA preferensi tersimpan (lihat docblock handle()).
                // Restorasi di `finally` supaya worker tidak mewariskan bahasa
                // user ini ke job berikutnya.
                $previousLocale = app()->getLocale();
                app()->setLocale($this->localeForPurchase($purchase));

                // Teks dari formatter ditulis gaya Markdown lama; dikirim ke
                // MarkdownV2 harus lewat konverter dulu (kalau tidak, karakter
                // seperti `.` dan `(` ditolak Telegram).
                $statusText = TelegramMarkdown::fromLegacy(
                    app(BotMessageFormatter::class)->formatStatus($payload, BotGatewayCapabilities::SOURCE_TELEGRAM)['text'],
                );

                $response = Http::timeout(10)->post(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    [
                        'chat_id' => $chatId,
                        'text' => $statusText,
                        'parse_mode' => 'MarkdownV2',
                    ],
                );

                if ($response->successful() && ($response->json('ok') ?? false)) {
                    Cache::put($cacheKey, true, now()->addHours(24));
                } else {
                    // Pengaman: kalau format ditolak, kirim ulang tanpa format.
                    // Notifikasi status order terlalu penting untuk hilang.
                    Log::warning('NotifyBotOrderStatus: notif berformat ditolak, kirim ulang tanpa format', [
                        'order_id' => $orderId,
                        'transition' => $transition,
                        'response' => $response->json(),
                    ]);

                    $retry = Http::timeout(10)->post(
                        "https://api.telegram.org/bot{$token}/sendMessage",
                        [
                            'chat_id' => $chatId,
                            'text' => app(BotMessageFormatter::class)->formatStatus($payload, BotGatewayCapabilities::SOURCE_TELEGRAM)['text'],
                        ],
                    );

                    if ($retry->successful() && ($retry->json('ok') ?? false)) {
                        Cache::put($cacheKey, true, now()->addHours(24));
                    } else {
                        Log::warning('NotifyBotOrderStatus: gagal kirim notif Telegram', [
                            'order_id' => $orderId,
                            'transition' => $transition,
                            'response' => $retry->json(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('NotifyBotOrderStatus: exception kirim notif Telegram', [
                    'order_id' => $orderId,
                    'transition' => $transition,
                    'error' => $e->getMessage(),
                ]);
            } finally {
                // Worker queue dipakai ulang antar job; bahasa user ini TIDAK
                // boleh menempel ke notifikasi user berikutnya.
                app()->setLocale($previousLocale ?? app()->getLocale());
            }
        }
    }

    /**
     * Bahasa untuk notifikasi Telegram dari PREFERENSI TERSIMPAN saja.
     *
     * Tidak membaca `language_code` (`$context`) karena di worker antrean
     * konteks itu tidak ada, dan tidak membaca `app()->getLocale()` karena
     * nilainya sisa job sebelumnya. Yang dibaca: baris
     * `bot_locale_preferences` — pilihan eksplisit user (`/bahasa`) atau benih
     * auto-deteksi Telegram yang ditulis saat ia berinteraksi.
     *
     * Ini juga alasan kunci lookup-nya `external_user_id` milik adapter
     * (`telegram:<bot_scope>:<user_id>`): bentuk `source` + `external_user_id`
     * adalah yang ditulis `BotLocale::seed()`/`setForContext()`. Bentuk kanonik
     * `telegram:<id>` (dipakai `gateway_principal`) akan MENEMU KOSONG — chat_id
     * untuk pengiriman tetap dari `gateway_principal`, tapi bahasanya dari baris
     * preferensi.
     *
     * Tidak ada baris → `'id'` (pasar utama; bukan tebakan, bukan default panel
     * yang bisa berubah-ubah tanpa sepengetahuan user).
     */
    private function localeForPurchase(Pembelian $purchase): string
    {
        $scope = (string) config('services.telegram-bot-api.bot_scope', 'default');

        $digits = TelegramIdentity::digits($purchase->gateway_principal)
            ?? TelegramIdentity::digits($purchase->email_pembeli);

        if ($digits === null) {
            return self::DEFAULT_LOCALE;
        }

        $locale = BotLocalePreference::query()
            ->where('source', BotGatewayCapabilities::SOURCE_TELEGRAM)
            ->where('external_user_id', 'telegram:' . $scope . ':' . $digits)
            ->value('locale');

        return is_string($locale) && $locale !== '' ? $locale : self::DEFAULT_LOCALE;
    }
}
