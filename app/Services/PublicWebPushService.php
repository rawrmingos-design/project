<?php

namespace App\Services;

use App\Models\PublicPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class PublicWebPushService
{
    public function vapidPublicKey(): string
    {
        return trim((string) config('services.webpush.vapid.public_key'));
    }

    public function isConfigured(): bool
    {
        return $this->vapidPublicKey() !== ''
            && trim((string) config('services.webpush.vapid.private_key')) !== ''
            && trim((string) config('services.webpush.vapid.subject')) !== '';
    }

    public function sendToSubscription(PublicPushSubscription $subscription, array $payload): array
    {
        if (! $this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Konfigurasi VAPID web push belum lengkap.',
                'remove_subscription' => false,
            ];
        }

        $auth = [
            'VAPID' => [
                'subject' => trim((string) config('services.webpush.vapid.subject')),
                'publicKey' => $this->vapidPublicKey(),
                'privateKey' => trim((string) config('services.webpush.vapid.private_key')),
            ],
        ];

        $webPush = new WebPush($auth);
        $webPush->queueNotification(
            Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
            ]),
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        try {
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    return [
                        'success' => true,
                        'message' => 'Push berhasil dikirim.',
                        'remove_subscription' => false,
                    ];
                }

                $reason = $report->getReason() ?: 'Unknown delivery failure';
                $statusCode = method_exists($report, 'getResponse') && $report->getResponse()
                    ? $report->getResponse()->getStatusCode()
                    : null;

                return [
                    'success' => false,
                    'message' => 'Push gagal dikirim: ' . $reason,
                    'remove_subscription' => in_array($statusCode, [404, 410], true),
                ];
            }
        } catch (Throwable $exception) {
            return [
                'success' => false,
                'message' => 'Push gagal dikirim: ' . $exception->getMessage(),
                'remove_subscription' => false,
            ];
        }

        return [
            'success' => false,
            'message' => 'Push gagal dikirim: tidak ada response delivery.',
            'remove_subscription' => false,
        ];
    }

    public function broadcastToActiveSubscriptions(array $payload): array
    {
        $successCount = 0;
        $failedCount = 0;
        $failedMessages = [];

        $subscriptions = PublicPushSubscription::query()
            ->where('is_active', true)
            ->whereNull('unsubscribed_at')
            ->get();

        foreach ($subscriptions as $subscription) {
            $result = $this->sendToSubscription($subscription, $payload);

            if ($result['success']) {
                $successCount++;
                $subscription->forceFill(['last_seen_at' => now()])->save();
                continue;
            }

            $failedCount++;
            $failedMessages[] = $result['message'];

            if ($result['remove_subscription'] ?? false) {
                $subscription->forceFill([
                    'is_active' => false,
                    'unsubscribed_at' => now(),
                ])->save();
            }
        }

        return [
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'failed_messages' => array_values(array_unique($failedMessages)),
            'total' => $subscriptions->count(),
        ];
    }
}
