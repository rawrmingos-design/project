<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBotWebhookJob;
use App\Services\Bot\Adapters\FonnteAdapter;
use App\Services\Bot\Adapters\OpenWaAdapter;
use App\Services\Settings\DatabaseSettingsBridge;
use App\Services\Telegram\TelegramUpdateReplayGuard;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class BotWebhookController extends Controller
{
    public function telegram(
        Request $request,
        TelegramUpdateReplayGuard $replayGuard,
        DatabaseSettingsBridge $settings,
    ): JsonResponse {
        $request->attributes->set('bot_correlation_id', (string) Str::uuid());

        // Setelan panel WAJIB siap sebelum apa pun dibaca: secret webhook,
        // token bot, dan saklar order semuanya berasal dari `setting_webs`.
        // Tanpa ini seluruh request ditolak 401 di server yang tokennya hanya
        // diisi lewat panel (bukan .env).
        $settings->apply();

        $secret = (string) config('services.telegram-bot-api.webhook_secret');
        $headerToken = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret === '' || ! hash_equals($secret, $headerToken)) {
            $invalidKey = 'bot-invalid:ip:' . $request->ip();
            $invalidLimit = max(1, (int) config('rate_limits.callbacks.bot_invalid_per_minute', 20));

            if (RateLimiter::tooManyAttempts($invalidKey, $invalidLimit)) {
                return response()->json(['message' => 'Too Many Requests'], 429);
            }

            RateLimiter::hit($invalidKey, 60);

            Log::warning('Telegram webhook authentication failed.', [
                'ip' => $request->ip(),
                'secret_configured' => $secret !== '',
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (! $replayGuard->claim(
            (string) config('services.telegram-bot-api.bot_scope', 'default'),
            $request->input('update_id'),
        )) {
            return response()->json(['status' => 'duplicate']);
        }

        // SERAHKAN KE ANTREAN, jangan diproses di sini.
        //
        // Satu update membutuhkan ~3,2 detik dan 98% waktunya menunggu balasan
        // api.telegram.org. Selama pekerjaan itu menempel di siklus request,
        // Telegram menunggu — dan kalau melewati ambangnya (~10 s) ia mencatat
        // "Read timeout expired" lalu mengirim ulang update yang sama.
        //
        // Workers `webhook` sudah berjalan di supervisor (2 proses), jadi tidak
        // ada infrastruktur baru yang dibutuhkan.
        //
        // Penyerahan dibungkus percobaan: kalau antrean tidak bisa dijangkau
        // (mis. Redis mati), klaim replay dibatalkan dan request dijawab `500`.
        // Itu lebih baik daripada menjawab `200` untuk pekerjaan yang tidak
        // pernah masuk antrean: Telegram akan mengirim ulang, dan karena klaim
        // sudah dilepas, percobaan berikutnya tidak ditolak sebagai duplikat.
        try {
            // Sengaja lewat bus dispatcher, BUKAN `ProcessBotWebhookJob::dispatch()`.
            // Bentuk statis itu mengembalikan `PendingDispatch` yang baru
            // mendorong job saat destruktornya jalan — yaitu di luar blok
            // `try` ini, sehingga kegagalan antrean tidak akan tertangkap dan
            // request tetap dijawab `200`. Lewat bus, dorongannya terjadi tepat
            // di sini dan kegagalannya bisa ditangani.
            app(Dispatcher::class)->dispatch(new ProcessBotWebhookJob(
                $request->all(),
                'telegram',
                (string) $request->attributes->get('bot_correlation_id'),
            ));
        } catch (\Throwable $e) {
            $replayGuard->release(
                (string) config('services.telegram-bot-api.bot_scope', 'default'),
                $request->input('update_id'),
            );

            Log::error('BotWebhookController: gagal menyerahkan update ke antrean.', [
                'correlation_id' => $request->attributes->get('bot_correlation_id'),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['status' => 'queue_unavailable'], 500);
        }

        return response()->json(['status' => 'queued']);
    }

    public function fonnte(Request $request, FonnteAdapter $adapter): JsonResponse
    {
        $request->attributes->set('bot_correlation_id', (string) Str::uuid());

        return $adapter->handle($request);
    }

    public function openwa(Request $request, OpenWaAdapter $adapter): JsonResponse
    {
        $request->attributes->set('bot_correlation_id', (string) Str::uuid());

        return $adapter->handle($request);
    }
}
