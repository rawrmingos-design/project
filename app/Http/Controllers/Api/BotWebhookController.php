<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Bot\Adapters\FonnteAdapter;
use App\Services\Bot\Adapters\TelegramAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotWebhookController extends Controller
{
    public function telegram(Request $request, TelegramAdapter $adapter): JsonResponse
    {
        // Simple security: check token in path if we registered webhook with path
        // e.g. /api/webhooks/bot/telegram/{token}
        // For MVP, we assume the route is protected or secret enough
        return $adapter->handle($request);
    }

    public function fonnte(Request $request, FonnteAdapter $adapter): JsonResponse
    {
        // Fonnte webhook validation can be done here (e.g. check device token header if any)
        return $adapter->handle($request);
    }
}
