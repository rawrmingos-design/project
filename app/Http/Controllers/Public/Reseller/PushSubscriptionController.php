<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\ResellerPushSubscription;
use App\Services\ResellerWebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly ResellerWebPushService $webPushService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription.endpoint' => ['required', 'url'],
            'subscription.keys.p256dh' => ['required', 'string'],
            'subscription.keys.auth' => ['required', 'string'],
            'subscription.contentEncoding' => ['nullable', 'string', 'max:32'],
        ]);

        $subscription = ResellerPushSubscription::updateOrCreate(
            ['endpoint' => $validated['subscription']['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $validated['subscription']['keys']['p256dh'],
                'auth_token' => $validated['subscription']['keys']['auth'],
                'content_encoding' => $validated['subscription']['contentEncoding'] ?? null,
                'user_agent' => substr((string) $request->userAgent(), 0, 255) ?: null,
            ]
        );

        return response()->json([
            'message' => 'Push subscription berhasil disimpan.',
            'subscription_id' => $subscription->id,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url'],
        ]);

        $deleted = $request->user()
            ->resellerPushSubscriptions()
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json([
            'message' => $deleted > 0
                ? 'Push subscription berhasil dihapus.'
                : 'Push subscription tidak ditemukan.',
        ], $deleted > 0 ? 200 : 404);
    }

    public function sendTest(Request $request): JsonResponse
    {
        $subscriptions = $request->user()->resellerPushSubscriptions()->get();

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada device yang subscribe push notification.',
            ], 422);
        }

        if (! $this->webPushService->isConfigured()) {
            return response()->json([
                'message' => 'Konfigurasi VAPID web push belum lengkap.',
            ], 422);
        }

        $successCount = 0;
        $failedMessages = [];

        foreach ($subscriptions as $subscription) {
            $result = $this->webPushService->sendTestNotification($subscription);

            if ($result['success']) {
                $successCount++;
                continue;
            }

            if ($result['remove_subscription'] ?? false) {
                $subscription->delete();
            }

            $failedMessages[] = $result['message'];
        }

        if ($successCount === 0) {
            return response()->json([
                'message' => $failedMessages[0] ?? 'Test push gagal dikirim.',
            ], 422);
        }

        return response()->json([
            'message' => 'Test push berhasil dikirim ke ' . $successCount . ' device.',
            'success_count' => $successCount,
            'failed_messages' => $failedMessages,
        ]);
    }
}
