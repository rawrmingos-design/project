<?php

namespace App\Listeners;

use App\Events\InvoiceStatusUpdated;
use App\Models\Pembelian;
use App\Services\Bot\BotMessageFormatter;
use App\Services\WhatsappNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyBotOrderStatusListener implements ShouldQueue
{
    /**
     * Transisi yang wajib di-notifikasi ke pembeli via bot:
     *  - Payment Lunas (order masih diproses)
     *  - Order Sukses (provider balas sukses)
     *  - Order Gagal/Batal (provider balas gagal)
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

        if ($isSuccess) {
            $transition = 'success';
            $summary = 'Top Up Berhasil';
        } elseif ($isFailed) {
            $transition = 'failed';
            $summary = 'Order Gagal';
        } else {
            $transition = 'paid';
            $summary = 'Pembayaran Diterima';
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
            try {
                $response = Http::timeout(10)->post(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    [
                        'chat_id' => str_replace('telegram:', '', (string) $purchase->gateway_principal),
                        'text' => app(BotMessageFormatter::class)->formatStatus($payload)['text'],
                        'parse_mode' => 'Markdown',
                    ],
                );

                if ($response->successful() && ($response->json('ok') ?? false)) {
                    Cache::put($cacheKey, true, now()->addHours(24));
                } else {
                    Log::warning('NotifyBotOrderStatus: gagal kirim notif Telegram', [
                        'order_id' => $orderId,
                        'transition' => $transition,
                        'response' => $response->json(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('NotifyBotOrderStatus: exception kirim notif Telegram', [
                    'order_id' => $orderId,
                    'transition' => $transition,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
