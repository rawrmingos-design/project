<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Bot\Adapters\FonnteAdapter;
use App\Services\Bot\Adapters\TelegramAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BotWebhookController extends Controller
{
    public function telegram(Request $request, TelegramAdapter $adapter): JsonResponse
    {
        $secret = (string) config('services.telegram-bot-api.webhook_secret');
        $headerToken = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');

        if ($secret !== '' && ! hash_equals($secret, $headerToken)) {
            Log::warning('Telegram webhook authentication failed.', [
                'ip' => $request->ip(),
                'secret_configured' => $secret !== '',
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $adapter->handle($request);
    }

    public function fonnte(Request $request, FonnteAdapter $adapter): JsonResponse
    {
        $expectedToken = (string) config('services.fonnte.device_token');
        $providedToken = (string) ($request->header('Authorization') ?: $request->input('device_token', ''));

        if ($providedToken !== '' && $expectedToken !== '' && ! hash_equals($expectedToken, $providedToken)) {
            Log::warning('Fonnte webhook authentication failed.', [
                'ip' => $request->ip(),
                'secret_configured' => $expectedToken !== '',
            ]);

            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $adapter->handle($request);
    }
}
