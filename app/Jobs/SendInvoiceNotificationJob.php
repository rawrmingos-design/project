<?php

namespace App\Jobs;

use App\Models\InvoiceNotificationDelivery;
use App\Services\EmailNotificationService;
use App\Services\WhatsappNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInvoiceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(
        public readonly int $deliveryId,
        public readonly bool $force = false,
    ) {
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    public function handle(
        WhatsappNotificationService $whatsapp,
        EmailNotificationService $email,
    ): void {
        $delivery = DB::transaction(function (): ?InvoiceNotificationDelivery {
            $record = InvoiceNotificationDelivery::query()
                ->whereKey($this->deliveryId)
                ->lockForUpdate()
                ->first();

            if (! $record || ($record->status === InvoiceNotificationDelivery::STATUS_SENT && ! $this->force)) {
                return null;
            }

            $record->forceFill([
                'status' => InvoiceNotificationDelivery::STATUS_SENDING,
                'attempts' => (int) $record->attempts + 1,
                'locked_at' => now(),
                'last_error' => null,
            ])->save();

            return $record;
        });

        if (! $delivery) {
            return;
        }

        if ($delivery->channel === InvoiceNotificationDelivery::CHANNEL_WHATSAPP) {
            Cache::lock('invoice-notification:whatsapp-rate-limit', 10)->block(10, function (): void {
                usleep(1_000_000);
            });
        }

        $result = $this->send($delivery, $whatsapp, $email);
        $success = $delivery->channel === InvoiceNotificationDelivery::CHANNEL_EMAIL
            ? $result === true
            : (bool) ($result['success'] ?? false);

        if ($success) {
            $providerMessageId = is_array($result) ? ($result['provider_message_id'] ?? null) : null;
            $delivery->forceFill([
                'status' => InvoiceNotificationDelivery::STATUS_SENT,
                'provider_message_id' => $providerMessageId,
                'sent_at' => now(),
                'locked_at' => null,
                'next_attempt_at' => null,
                'last_error' => null,
            ])->save();

            return;
        }

        $errorCode = is_array($result) ? ($result['error_code'] ?? null) : null;
        $message = is_array($result)
            ? (string) ($result['message'] ?? 'Notification provider rejected delivery.')
            : 'Email provider rejected delivery.';
        $permanent = in_array($errorCode, ['template_unavailable', 'recipient_invalid'], true);

        $delivery->forceFill([
            'status' => InvoiceNotificationDelivery::STATUS_FAILED,
            'locked_at' => null,
            'last_error' => mb_substr($message, 0, 2000),
            'next_attempt_at' => $permanent
                ? null
                : now()->addSeconds($this->backoffForAttempt((int) $delivery->attempts)),
        ])->save();

        if (! $permanent) {
            throw new \RuntimeException($message);
        }
    }

    private function send(
        InvoiceNotificationDelivery $delivery,
        WhatsappNotificationService $whatsapp,
        EmailNotificationService $email,
    ): array|bool {
        if ($delivery->channel === InvoiceNotificationDelivery::CHANNEL_EMAIL) {
            return $email->sendTransactionEmail((string) $delivery->recipient, $delivery->payload_json ?? []);
        }

        return $whatsapp->sendNotification(
            (string) $delivery->recipient,
            $delivery->template_slug,
            $delivery->payload_json ?? [],
        );
    }

    private function backoffForAttempt(int $attempt): int
    {
        return [60, 300, 900, 3600][min(max($attempt - 1, 0), 3)];
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('invoice_notification.delivery_permanently_failed', [
            'delivery_id' => $this->deliveryId,
            'error' => $exception?->getMessage(),
        ]);
    }
}

