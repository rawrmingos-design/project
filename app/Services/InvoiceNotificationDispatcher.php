<?php

namespace App\Services;

use App\Jobs\SendInvoiceNotificationJob;
use App\Models\InvoiceNotificationDelivery;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Support\PembelianNotificationHelper;
use Illuminate\Support\Facades\DB;

class InvoiceNotificationDispatcher
{
    public const TRANSITION_PAYMENT_PENDING = 'payment_pending';
    public const TRANSITION_PAYMENT_PAID = 'payment_paid';
    public const TRANSITION_PROVIDER_SUCCESS = 'provider_success';
    public const TRANSITION_PROVIDER_FAILED = 'provider_failed';
    public const TRANSITION_PAYMENT_FAILED = 'payment_failed';

    /**
     * Create idempotent delivery intents and dispatch their jobs after commit.
     *
     * @return array<int, InvoiceNotificationDelivery>
     */
    public function dispatchForTransition(Pembelian $record, string $transition): array
    {
        $templateSlug = $this->templateSlugForTransition($transition);
        $record->loadMissing(['pembayaran', 'user']);
        $settings = SettingWeb::query()->first();
        $payload = PembelianNotificationHelper::payload($record);
        $deliveries = [];

        $channels = [
            InvoiceNotificationDelivery::CHANNEL_WHATSAPP => [
                'enabled' => $settings?->invoice_notify_via_whatsapp !== false,
                'recipient' => PembelianNotificationHelper::whatsappTarget($record),
                'provider' => strtolower(trim((string) ($settings?->wa_provider ?? ''))),
            ],
            InvoiceNotificationDelivery::CHANNEL_EMAIL => [
                'enabled' => $settings?->invoice_notify_via_email !== false,
                'recipient' => PembelianNotificationHelper::emailTarget($record),
                'provider' => strtolower(trim((string) ($settings?->mail_mailer ?? 'smtp'))),
            ],
        ];

        foreach ($channels as $channel => $definition) {
            if (! $definition['enabled'] || blank($definition['recipient'])) {
                continue;
            }

            $idempotencyKey = hash('sha256', implode('|', [
                $record->order_id,
                (int) $record->invoice_version,
                $channel,
                $transition,
            ]));

            $delivery = InvoiceNotificationDelivery::query()->firstOrCreate(
                ['idempotency_key' => $idempotencyKey],
                [
                    'order_id' => $record->order_id,
                    'invoice_version' => (int) $record->invoice_version,
                    'channel' => $channel,
                    'transition' => $transition,
                    'status' => InvoiceNotificationDelivery::STATUS_PENDING,
                    'provider' => $definition['provider'] ?: null,
                    'template_slug' => $templateSlug,
                    'recipient' => (string) $definition['recipient'],
                    'recipient_hash' => hash('sha256', (string) $definition['recipient']),
                    'payload_json' => $payload,
                    'idempotency_key' => $idempotencyKey,
                ],
            );

            $deliveries[] = $delivery;

            if ($delivery->wasRecentlyCreated) {
                DB::afterCommit(static function () use ($delivery): void {
                    SendInvoiceNotificationJob::dispatch($delivery->getKey());
                });
            }
        }

        return $deliveries;
    }

    private function templateSlugForTransition(string $transition): string
    {
        return match ($transition) {
            self::TRANSITION_PROVIDER_SUCCESS => 'transaction_success',
            self::TRANSITION_PROVIDER_FAILED, self::TRANSITION_PAYMENT_FAILED => 'transaction_failed',
            self::TRANSITION_PAYMENT_PENDING, self::TRANSITION_PAYMENT_PAID => 'transaction_pending',
            default => throw new \InvalidArgumentException('Unsupported invoice notification transition: ' . $transition),
        };
    }
}

