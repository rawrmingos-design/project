<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Bot\Adapters\FonnteAdapter;
use App\Services\Bot\Adapters\TelegramAdapter;
use App\Services\Telegram\TelegramUpdateReplayGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class BotWebhookController extends Controller
{
    public function telegram(Request $request, TelegramAdapter $adapter, TelegramUpdateReplayGuard $replayGuard): JsonResponse
    {
        $request->attributes->set('bot_correlation_id', (string) Str::uuid());
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

        return $adapter->handle($request);
    }

    public function fonnte(Request $request, FonnteAdapter $adapter): JsonResponse
    {
        $request->attributes->set('bot_correlation_id', (string) Str::uuid());

        return $adapter->handle($request);
    }
}
