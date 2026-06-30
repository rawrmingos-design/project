<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PublicPushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subscription.endpoint' => ['required', 'url'],
            'subscription.keys.p256dh' => ['required', 'string'],
            'subscription.keys.auth' => ['required', 'string'],
            'subscription.contentEncoding' => ['nullable', 'string', 'max:32'],
            'device_label' => ['nullable', 'string', 'max:120'],
            'locale' => ['nullable', 'string', 'max:16'],
        ]);

        $endpoint = $validated['subscription']['endpoint'];
        $now = Carbon::now();

        $subscription = PublicPushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => $request->user()?->id,
                'session_id_hash' => hash('sha256', $request->session()->getId()),
                'endpoint' => $endpoint,
                'public_key' => $validated['subscription']['keys']['p256dh'],
                'auth_token' => $validated['subscription']['keys']['auth'],
                'content_encoding' => $validated['subscription']['contentEncoding'] ?? null,
                'device_label' => $validated['device_label'] ?? null,
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'locale' => $validated['locale'] ?? substr((string) $request->header('Accept-Language'), 0, 16) ?: null,
                'last_seen_at' => $now,
                'subscribed_at' => $now,
                'unsubscribed_at' => null,
                'is_active' => true,
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

        $subscription = PublicPushSubscription::query()
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'Push subscription tidak ditemukan.',
            ], 404);
        }

        $subscription->forceFill([
            'is_active' => false,
            'unsubscribed_at' => Carbon::now(),
        ])->save();

        return response()->json([
            'message' => 'Push subscription berhasil dihapus.',
        ]);
    }
}
