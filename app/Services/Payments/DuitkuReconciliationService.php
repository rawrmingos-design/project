<?php

namespace App\Services\Payments;

use App\Models\InboundSourceEvent;
use App\Models\Pembayaran;
use Duitku\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DuitkuReconciliationService
{
    private const POLL_THROTTLE_SECONDS = 30;

    public function __construct(
        private readonly DuitkuPopClient $client,
        private readonly DuitkuPaymentSettlementService $settlementService,
    ) {
    }

    public function reconcileByOrderId(string $orderId, bool $throttled = false): array
    {
        $payment = Pembayaran::query()
            ->where('order_id', $orderId)
            ->whereNotNull('duitku_merchant_order_id')
            ->latest('id')
            ->first();

        if (! $payment) {
            return [
                'decision' => 'not_duitku',
                'order_id' => $orderId,
            ];
        }

        if ($throttled) {
            if ($payment->isExpiredUnpaid()) {
                return [
                    'decision' => 'expired_locally',
                    'order_id' => $orderId,
                ];
            }

            $key = 'duitku:reconcile:payment:' . $payment->getKey();
            if (! Cache::add($key, true, now()->addSeconds(self::POLL_THROTTLE_SECONDS))) {
                return [
                    'decision' => 'throttled',
                    'order_id' => $orderId,
                ];
            }
        }

        return $this->reconcile($payment);
    }

    public function reconcileByMerchantOrderId(string $merchantOrderId): array
    {
        $payment = Pembayaran::query()
            ->where('duitku_merchant_order_id', $merchantOrderId)
            ->latest('id')
            ->first();

        if (! $payment) {
            throw new RuntimeException('Duitku payment not found.');
        }

        return $this->reconcile($payment);
    }

    public function reconcile(Pembayaran $payment): array
    {
        $merchantOrderId = trim((string) $payment->duitku_merchant_order_id);
        if ($merchantOrderId === '') {
            throw new RuntimeException('Duitku merchant order ID is missing.');
        }

        $settings = \App\Services\Payments\DuitkuConfiguration::settings();
        $config = \App\Services\Payments\DuitkuConfiguration::load();
        $status = $this->client->transactionStatusForPayment(
            $merchantOrderId,
            $config,
            $payment->duitku_api_mode,
            $payment->duitku_payment_code ?: $payment->metode,
        );
        $statusCode = trim((string) ($status['statusCode'] ?? ''));

        $decision = match ($statusCode) {
            '00' => $this->settle($payment, $status),
            '01' => 'pending',
            default => 'unknown',
        };

        $this->recordEvent($payment, $statusCode, $decision);
        Log::log($decision === 'unknown' ? 'warning' : 'info', 'duitku.reconciliation.' . $decision, [
            'payment_id' => $payment->getKey(),
            'order_id' => $payment->order_id,
            'merchant_order_id' => $merchantOrderId,
            'status_code' => $statusCode,
            'payment_code' => $payment->duitku_payment_code,
            'api_mode' => $payment->duitku_api_mode,
        ]);

        return [
            'decision' => $decision,
            'status_code' => $statusCode,
            'status_message' => $status['statusMessage'] ?? null,
            'order_id' => $payment->order_id,
            'merchant_order_id' => $merchantOrderId,
        ];
    }

    private function settle(Pembayaran $payment, array $status): string
    {
        return DB::transaction(function () use ($payment, $status): string {
            $lockedPayment = Pembayaran::query()->lockForUpdate()->findOrFail($payment->getKey());
            $amount = $status['amount'] ?? null;

            if ($amount !== null && (int) $amount !== (int) $lockedPayment->harga) {
                throw new RuntimeException('Duitku reconciliation amount mismatch.');
            }

            return $this->settlementService->settle(
                $lockedPayment,
                $status['reference'] ?? null,
                $lockedPayment->duitku_merchant_order_id,
            );
        }, 3);
    }



        return $settings;
    }



    private function recordEvent(Pembayaran $payment, string $statusCode, string $decision): void
    {
        try {
            InboundSourceEvent::query()->create([
                'source_domain' => 'payment_gateway',
                'source_name' => 'duitku',
                'route_uri' => 'internal/reconciliation',
                'method' => 'INTERNAL',
                'mode' => 'application',
                'decision' => 'reconciled_' . $decision,
                'response_status' => 200,
                'details' => [
                    'payment_id' => $payment->getKey(),
                    'order_id' => $payment->order_id,
                    'merchant_order_id' => $payment->duitku_merchant_order_id,
                    'payment_code' => $payment->duitku_payment_code,
                    'status_code' => $statusCode,
                    'api_mode' => $payment->duitku_api_mode,
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('duitku.reconciliation.audit_failed', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

