<?php

namespace App\Services\Payments;

use App\Models\InboundSourceEvent;
use App\Models\Pembayaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DuitkuCallbackService
{
    private const REQUIRED_FIELDS = [
        'merchantCode',
        'amount',
        'merchantOrderId',
        'resultCode',
        'signature',
    ];

    public function __construct(
        private readonly DuitkuPaymentSettlementService $settlementService,
    ) {
    }

    public function processCallback(array $payload, object $settings): array
    {
        try {
            $payload = $this->validatePayload($payload, $settings);
            $result = DB::transaction(function () use ($payload): array {
                $payment = $this->resolvePayment($payload);

                if ((int) $payload['amount'] !== (int) $payment->harga) {
                    throw new DuitkuCallbackException('Invalid amount', 400, 'amount_mismatch');
                }

                if ($payload['resultCode'] === '01') {
                    return [
                        'decision' => 'pending',
                        'payment' => $payment,
                    ];
                }

                if ($payload['resultCode'] !== '00') {
                    return [
                        'decision' => 'unknown',
                        'payment' => $payment,
                    ];
                }

                $decision = $this->settlementService->settle(
                    $payment,
                    $payload['reference'] ?: null,
                    $payload['merchantOrderId'],
                );

                return [
                    'decision' => $decision,
                    'payment' => $payment,
                ];
            }, 3);

            if ($result['decision'] === 'unknown') {
                try {
                    $reconciled = app(DuitkuReconciliationService::class)->reconcile($result['payment']);
                    if (in_array($reconciled['decision'] ?? null, ['paid', 'duplicate', 'pending'], true)) {
                        $result['decision'] = 'reconciled_' . $reconciled['decision'];
                    } else {
                        throw new DuitkuCallbackException('Unrecognized result code', 409, 'unknown_result_code');
                    }
                } catch (DuitkuCallbackException $exception) {
                    throw $exception;
                } catch (Throwable $exception) {
                    Log::warning('duitku.callback.reconciliation_failed', array_merge(
                        $this->logContext($payload, $result['payment']),
                        ['error' => $exception->getMessage()],
                    ));

                    throw new DuitkuCallbackException('Unable to reconcile callback', 503, 'reconciliation_failed');
                }
            }

            $this->recordEvent($payload, $result['decision'], 200, $result['payment']);
            Log::info('duitku.callback.' . $result['decision'], $this->logContext($payload, $result['payment']));

            return [
                'status' => 200,
                'body' => 'SUCCESS',
                'decision' => $result['decision'],
            ];
        } catch (DuitkuCallbackException $exception) {
            $this->recordEvent($payload, 'rejected', $exception->status, null, $exception->reason);
            Log::warning('duitku.callback.' . $exception->reason, $this->logContext($payload));

            throw $exception;
        }
    }

    private function validatePayload(array $payload, object $settings): array
    {
        if ($payload === []) {
            throw new DuitkuCallbackException('Invalid callback payload', 400, 'invalid_payload');
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (! array_key_exists($field, $payload) || ! is_scalar($payload[$field])) {
                throw new DuitkuCallbackException('Invalid callback payload', 400, 'invalid_payload');
            }
        }

        foreach (['productDetail', 'additionalParam', 'paymentCode', 'merchantUserId', 'reference', 'spUserHash'] as $field) {
            if (array_key_exists($field, $payload) && ! is_null($payload[$field]) && ! is_scalar($payload[$field])) {
                throw new DuitkuCallbackException('Invalid callback payload', 400, 'invalid_payload');
            }
        }

        $normalized = [];
        foreach ($payload as $key => $value) {
            $normalized[$key] = is_scalar($value) ? trim((string) $value) : null;
        }

        if ($normalized['merchantCode'] === ''
            || $normalized['merchantOrderId'] === ''
            || $normalized['resultCode'] === ''
            || $normalized['signature'] === ''
            || preg_match('/^\d+$/', $normalized['amount']) !== 1) {
            throw new DuitkuCallbackException('Invalid callback payload', 400, 'invalid_payload');
        }

        if (! hash_equals((string) $settings->duitku_merchant_code, $normalized['merchantCode'])) {
            throw new DuitkuCallbackException('Invalid merchant', 400, 'merchant_mismatch');
        }

        $expectedSignature = md5(
            $normalized['merchantCode']
            . $normalized['amount']
            . $normalized['merchantOrderId']
            . (string) $settings->duitku_merchant_key
        );

        if (! hash_equals($expectedSignature, strtolower($normalized['signature']))) {
            throw new DuitkuCallbackException('Invalid signature', 400, 'invalid_signature');
        }

        return $normalized;
    }

    private function resolvePayment(array $payload): Pembayaran
    {
        $reference = $payload['reference'] ?? '';

        if ($reference !== '') {
            $payment = Pembayaran::query()
                ->where(function ($query) use ($reference): void {
                    $query->where('duitku_reference', $reference)
                        ->orWhere('reference', $reference);
                })
                ->lockForUpdate()
                ->first();

            if ($payment) {
                if (! hash_equals((string) $payment->duitku_merchant_order_id, $payload['merchantOrderId'])) {
                    throw new DuitkuCallbackException('Payment identity mismatch', 400, 'identity_mismatch');
                }

                return $payment;
            }
        }

        $payments = Pembayaran::query()
            ->where('duitku_merchant_order_id', $payload['merchantOrderId'])
            ->whereIn('status', ['Belum Lunas', 'Unpaid', 'Pending'])
            ->latest('id')
            ->lockForUpdate()
            ->get();

        if ($payments->count() > 1) {
            throw new DuitkuCallbackException('Ambiguous payment identity', 409, 'ambiguous_payment');
        }

        if ($payments->count() === 1) {
            return $payments->first();
        }

        $terminalPayment = Pembayaran::query()
            ->where('duitku_merchant_order_id', $payload['merchantOrderId'])
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($terminalPayment) {
            return $terminalPayment;
        }

        throw new DuitkuCallbackException('Payment not found', 503, 'payment_not_found');
    }

    private function recordEvent(
        array $payload,
        string $decision,
        int $status,
        ?Pembayaran $payment = null,
        ?string $reason = null,
    ): void {
        try {
            InboundSourceEvent::query()->create([
                'source_domain' => 'payment_gateway',
                'source_name' => 'duitku',
                'route_uri' => 'wejizy/duitku/callback',
                'route_name' => 'duitku.callback',
                'method' => 'POST',
                'mode' => 'application',
                'decision' => $decision,
                'reason' => $reason,
                'response_status' => $status,
                'details' => array_filter([
                    'payment_id' => $payment?->id,
                    'order_id' => $payment?->order_id,
                    'merchant_order_id' => $payload['merchantOrderId'] ?? null,
                    'reference' => $payload['reference'] ?? null,
                    'payment_code' => $payload['paymentCode'] ?? $payment?->duitku_payment_code,
                    'result_code' => $payload['resultCode'] ?? null,
                    'api_mode' => $payment?->duitku_api_mode,
                    'payload_hash' => $this->payloadHash($payload),
                ], static fn (mixed $value): bool => $value !== null && $value !== ''),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('duitku.callback.audit_failed', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function payloadHash(array $payload): string
    {
        unset($payload['signature']);
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES));
    }

    private function logContext(array $payload, ?Pembayaran $payment = null): array
    {
        return array_filter([
            'payment_id' => $payment?->id,
            'order_id' => $payment?->order_id,
            'merchant_order_id' => $payload['merchantOrderId'] ?? null,
            'reference' => $payload['reference'] ?? null,
            'payment_code' => $payload['paymentCode'] ?? $payment?->duitku_payment_code,
            'result_code' => $payload['resultCode'] ?? null,
            'api_mode' => $payment?->duitku_api_mode,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
