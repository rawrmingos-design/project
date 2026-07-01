<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\PublicPushNotificationDelivery;
use App\Models\PublicPushSubscription;
use App\Models\PushNotificationTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;

class PublicOrderPushNotificationService
{
    public const EVENT_ORDER_CREATED = 'order_created';
    public const EVENT_PAYMENT_SUCCESS = 'payment_success';
    public const EVENT_ORDER_SUCCESS = 'order_success';

    public function __construct(private readonly PublicWebPushService $webPush)
    {
    }

    public function notifyOrderCreated(Pembelian $order, ?string $sessionId = null): array
    {
        return $this->sendOnce($order, self::EVENT_ORDER_CREATED, $this->payloadForEvent($order, self::EVENT_ORDER_CREATED, [
            'title' => 'Pesanan berhasil dibuat',
            'body' => 'Segera lakukan pembayaran untuk invoice #{display_order_id}.',
            'tag' => 'order-created-' . $order->order_id,
        ]), $sessionId);
    }

    public function notifyPaymentSuccess(Pembelian $order, ?string $sessionId = null): array
    {
        return $this->sendOnce($order, self::EVENT_PAYMENT_SUCCESS, $this->payloadForEvent($order, self::EVENT_PAYMENT_SUCCESS, [
            'title' => 'Pembayaran berhasil',
            'body' => 'Pembayaran invoice #{display_order_id} sudah diterima. Pesanan sedang diproses.',
            'tag' => 'payment-success-' . $order->order_id,
        ]), $sessionId);
    }

    public function notifyOrderSuccess(Pembelian $order, ?string $sessionId = null): array
    {
        return $this->sendOnce($order, self::EVENT_ORDER_SUCCESS, $this->payloadForEvent($order, self::EVENT_ORDER_SUCCESS, [
            'title' => 'Pesanan berhasil',
            'body' => 'Pesanan #{display_order_id} berhasil diproses.',
            'tag' => 'order-success-' . $order->order_id,
        ]), $sessionId);
    }

    public function sendOnce(Pembelian $order, string $event, array $payload, ?string $sessionId = null): array
    {
        $subscriptions = $this->subscriptionsForOrder($order, $sessionId);
        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'total' => $subscriptions->count(),
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $delivery = PublicPushNotificationDelivery::create([
                    'public_push_subscription_id' => $subscription->id,
                    'order_id' => $order->order_id,
                    'event' => $event,
                    'endpoint_hash' => $subscription->endpoint_hash,
                    'status' => 'pending',
                    'payload' => $payload,
                ]);
            } catch (QueryException $exception) {
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }

                $results['skipped']++;
                continue;
            }

            $result = $this->webPush->sendToSubscription($subscription, $payload);

            if ($result['success'] ?? false) {
                $delivery->forceFill([
                    'status' => 'sent',
                    'error_message' => null,
                ])->save();

                $subscription->forceFill(['last_seen_at' => now()])->save();
                $results['sent']++;
                continue;
            }

            $delivery->forceFill([
                'status' => 'failed',
                'error_message' => $result['message'] ?? 'Push gagal dikirim.',
            ])->save();

            if ($result['remove_subscription'] ?? false) {
                $subscription->forceFill([
                    'is_active' => false,
                    'unsubscribed_at' => now(),
                ])->save();
            }

            $results['failed']++;
        }

        return $results;
    }

    public function subscriptionsForOrder(Pembelian $order, ?string $sessionId = null): Collection
    {
        $query = PublicPushSubscription::query()
            ->where('is_active', true)
            ->whereNull('unsubscribed_at');

        $user = $order->relationLoaded('user') ? $order->user : $order->user()->first();
        $sessionIdHash = $sessionId ? hash('sha256', $sessionId) : null;

        if (! $user && ! $sessionIdHash) {
            $subscriptionIds = PublicPushNotificationDelivery::query()
                ->where('order_id', $order->order_id)
                ->where('event', self::EVENT_ORDER_CREATED)
                ->where('status', 'sent')
                ->whereNotNull('public_push_subscription_id')
                ->pluck('public_push_subscription_id')
                ->all();

            if ($subscriptionIds === []) {
                return new Collection();
            }

            return $query->whereKey($subscriptionIds)->get();
        }

        $query->where(function ($query) use ($user, $sessionIdHash): void {
            if ($user) {
                $query->orWhere('user_id', $user->id);
            }

            if ($sessionIdHash) {
                $query->orWhere('session_id_hash', $sessionIdHash);
            }
        });

        return $query->get();
    }

    private function payloadForEvent(Pembelian $order, string $event, array $defaults): array
    {
        $template = PushNotificationTemplate::query()
            ->where('slug', $event)
            ->where('is_active', true)
            ->first();

        $title = $template?->title ?: $defaults['title'];
        $body = $template?->body ?: $defaults['body'];

        return [
            'title' => $this->limitPushText($this->renderTemplate($title, $order), 120),
            'body' => $this->limitPushText($this->renderTemplate($body, $order), 500),
            'url' => $this->invoiceUrl($order),
            'icon' => asset('assets/pwa/icon-192.png'),
            'tag' => $defaults['tag'],
        ];
    }

    private function renderTemplate(string $template, Pembelian $order): string
    {
        $replacements = [
            'order_id' => (string) $order->order_id,
            'display_order_id' => (string) ($order->display_order_id ?: $order->order_id),
            'product' => (string) ($order->layanan ?? ''),
            'amount' => 'Rp ' . number_format((int) ($order->harga ?? 0), 0, ',', '.'),
            'nickname' => (string) ($order->nickname ?? 'Pelanggan'),
            'status' => (string) ($order->status ?? ''),
            'sn' => (string) ($order->keterangan_sn ?? ''),
        ];

        foreach ($replacements as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }

    private function limitPushText(string $value, int $maxLength): string
    {
        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $maxLength - 1)) . '…';
    }

    private function invoiceUrl(Pembelian $order): string
    {
        return route('pembelian', ['order' => $order->order_id]);
    }
}
