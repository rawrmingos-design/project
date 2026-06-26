<?php

namespace App\Services;

use App\Models\ResellerPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class ResellerWebPushService
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

    public function sendTestNotification(ResellerPushSubscription $subscription): array
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

        $payload = json_encode([
            'title' => 'Test Notifikasi Reseller',
            'body' => 'Push notification reseller berhasil dikirim.',
            'url' => '/id/reseller/settings',
            'tag' => 'reseller-push-test',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $webPush = new WebPush($auth);
        $webPush->queueNotification(
            Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aesgcm',
            ]),
            $payload
        );

        try {
            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    return [
                        'success' => true,
                        'message' => 'Test push berhasil dikirim.',
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
}
