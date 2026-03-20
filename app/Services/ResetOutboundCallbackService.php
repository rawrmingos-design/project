<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\ResetCallbackDelivery;
use App\Support\PembelianStatus;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ResetOutboundCallbackService
{
    public function dispatchForStatusTransition(Pembelian $pembelian, string $previousStatus, string $currentStatus): array
    {
        $pembelian->loadMissing(['user', 'activeLayanan', 'pembayaran']);

        $previousStatus = PembelianStatus::normalize($previousStatus);
        $currentStatus = PembelianStatus::normalize($currentStatus);

        if (!$this->shouldDispatch($pembelian, $previousStatus, $currentStatus)) {
            return ['status' => 'skipped', 'reason' => 'not_dispatchable'];
        }

        $user = $pembelian->user;
        $callbackUrl = trim((string) ($user?->reset_callback_url ?? ''));

        if (!$user || !$user->reset_callback_enabled || $callbackUrl === '') {
            Log::info('Reset outbound callback skipped because user callback config is incomplete', [
                'order_id' => $pembelian->order_id,
                'invoice_version' => $pembelian->invoice_version,
                'username' => $pembelian->username,
            ]);

            return ['status' => 'skipped', 'reason' => 'callback_not_configured'];
        }

        $payload = $this->buildPayload($pembelian, $previousStatus, $currentStatus);
        $idempotencyKey = $this->buildIdempotencyKey($pembelian, $currentStatus);

        $delivery = ResetCallbackDelivery::firstOrNew([
            'idempotency_key' => $idempotencyKey,
        ]);

        $delivery->fill([
            'user_id' => $user->getKey(),
            'pembelian_id' => $pembelian->getKey(),
            'event_name' => 'reset_transaction.status_changed',
            'order_id' => $pembelian->order_id,
            'base_order_id' => $pembelian->base_order_id ?: $pembelian->order_id,
            'display_order_id' => $pembelian->display_order_id ?: $pembelian->order_id,
            'attempt_reference' => $pembelian->active_attempt_reference ?: $pembelian->display_order_id ?: $pembelian->order_id,
            'invoice_version' => (int) $pembelian->invoice_version,
            'target_status' => $currentStatus,
            'callback_url' => $callbackUrl,
            'signature_algorithm' => $this->resolveSigningAlgorithm($user->reset_callback_signing_algorithm),
            'payload' => $payload,
            'next_retry_at' => null,
        ]);

        if ($delivery->status === 'delivered') {
            Log::info('Reset outbound callback already delivered, skipping duplicate send', [
                'order_id' => $pembelian->order_id,
                'idempotency_key' => $delivery->idempotency_key,
            ]);

            return ['status' => 'skipped', 'reason' => 'already_delivered'];
        }

        $delivery->status = $delivery->exists ? $delivery->status : 'pending';
        $delivery->attempt_count = (int) $delivery->attempt_count + 1;
        $delivery->last_attempted_at = now();
        $delivery->save();

        return $this->deliver($delivery, (string) $user->reset_callback_secret);
    }

    public function replay(ResetCallbackDelivery $delivery): array
    {
        $delivery->loadMissing(['user', 'pembelian']);

        if (!$delivery->user || !$delivery->pembelian) {
            return ['status' => 'skipped', 'reason' => 'missing_relationships'];
        }

        $delivery->attempt_count = (int) $delivery->attempt_count + 1;
        $delivery->last_attempted_at = now();
        $delivery->status = 'pending';
        $delivery->save();

        return $this->deliver($delivery, (string) $delivery->user->reset_callback_secret);
    }

    private function deliver(ResetCallbackDelivery $delivery, string $secret): array
    {
        $timestamp = now()->toIso8601String();
        $payload = is_array($delivery->payload) ? $delivery->payload : [];
        $headers = [
            'X-Reset-Callback-Event' => $delivery->event_name,
            'X-Reset-Callback-Timestamp' => $timestamp,
            'X-Reset-Callback-Idempotency-Key' => $delivery->idempotency_key,
            'X-Reset-Callback-Signature-Alg' => $delivery->signature_algorithm,
            'X-Reset-Callback-Version' => (string) Arr::get($payload, 'meta.callback_version', 1),
        ];

        if ($secret !== '') {
            $headers['X-Reset-Callback-Signature'] = $this->signPayload($payload, $timestamp, $secret, $delivery->signature_algorithm);
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(10)
                ->withHeaders($headers)
                ->post($delivery->callback_url, $payload);

            $delivery->last_response_status = $response->status();
            $delivery->last_response_body = Str::limit($response->body(), 2000);

            if ($response->successful()) {
                $delivery->status = 'delivered';
                $delivery->delivered_at = now();
                $delivery->last_error = null;
                $delivery->save();

                Log::info('Reset outbound callback delivered', [
                    'order_id' => $delivery->order_id,
                    'idempotency_key' => $delivery->idempotency_key,
                    'status_code' => $delivery->last_response_status,
                ]);

                return ['status' => 'delivered', 'status_code' => $delivery->last_response_status];
            }

            $delivery->status = 'failed';
            $delivery->last_error = sprintf('HTTP %d response', $response->status());
            $delivery->next_retry_at = now()->addMinutes(5);
            $delivery->save();

            Log::warning('Reset outbound callback failed', [
                'order_id' => $delivery->order_id,
                'idempotency_key' => $delivery->idempotency_key,
                'status_code' => $delivery->last_response_status,
            ]);

            return ['status' => 'failed', 'status_code' => $delivery->last_response_status];
        } catch (Throwable $exception) {
            $delivery->status = 'failed';
            $delivery->last_error = $exception->getMessage();
            $delivery->next_retry_at = now()->addMinutes(5);
            $delivery->save();

            Log::error('Reset outbound callback threw an exception', [
                'order_id' => $delivery->order_id,
                'idempotency_key' => $delivery->idempotency_key,
                'error' => $exception->getMessage(),
            ]);

            return ['status' => 'failed', 'reason' => $exception->getMessage()];
        }
    }

    private function shouldDispatch(Pembelian $pembelian, string $previousStatus, string $currentStatus): bool
    {
        return (int) $pembelian->invoice_version > 0
            && $previousStatus !== $currentStatus;
    }

    private function buildIdempotencyKey(Pembelian $pembelian, string $currentStatus): string
    {
        return implode(':', [
            'reset-callback',
            $pembelian->getKey(),
            (int) $pembelian->invoice_version,
            PembelianStatus::apiStatusCode($currentStatus),
        ]);
    }

    private function buildPayload(Pembelian $pembelian, string $previousStatus, string $currentStatus): array
    {
        $service = $pembelian->activeLayanan;
        $payment = $pembelian->pembayaran;
        $user = $pembelian->user;

        return [
            'event' => 'reset_transaction.status_changed',
            'occurred_at' => now()->toIso8601String(),
            'meta' => [
                'callback_version' => max(1, (int) ($user?->reset_callback_version ?? 1)),
                'idempotency_key' => $this->buildIdempotencyKey($pembelian, $currentStatus),
            ],
            'order' => [
                'order_id' => $pembelian->base_order_id ?: $pembelian->order_id,
                'display_order_id' => $pembelian->display_order_id ?: $pembelian->order_id,
                'attempt_reference' => $pembelian->active_attempt_reference ?: $pembelian->display_order_id ?: $pembelian->order_id,
                'invoice_version' => (int) $pembelian->invoice_version,
                'reset_count' => (int) $pembelian->reset_count,
                'reset_status' => $pembelian->reset_status,
                'reference_number' => $payment?->reference,
            ],
            'transition' => [
                'from' => PembelianStatus::apiStatusCode($previousStatus),
                'to' => PembelianStatus::apiStatusCode($currentStatus),
                'from_normalized' => $previousStatus,
                'to_normalized' => $currentStatus,
                'is_final' => PembelianStatus::isFinal($currentStatus),
            ],
            'provider' => [
                'code' => $pembelian->active_provider_code,
                'sku' => $pembelian->active_provider_sku,
            ],
            'service' => [
                'id' => $service?->getKey(),
                'name' => $service?->layanan ?? $pembelian->layanan,
                'provider' => $service?->provider ?? $pembelian->active_provider_code,
                'provider_sku' => $service?->provider_id ?? $pembelian->active_provider_sku,
            ],
            'payment' => [
                'status' => $payment?->status,
                'method' => $payment?->metode,
                'reference' => $payment?->reference,
            ],
            'status' => [
                'database_label' => PembelianStatus::preferredDatabaseLabel($currentStatus),
                'normalized' => $currentStatus,
                'api_code' => PembelianStatus::apiStatusCode($currentStatus),
            ],
            'customer' => [
                'username' => $pembelian->username,
                'user_id' => $user?->getKey(),
            ],
        ];
    }

    private function resolveSigningAlgorithm(?string $algorithm): string
    {
        $algorithm = strtolower(trim((string) $algorithm));

        return $algorithm !== '' ? $algorithm : 'sha256';
    }

    private function signPayload(array $payload, string $timestamp, string $secret, string $algorithm): string
    {
        $normalizedAlgorithm = $this->resolveSigningAlgorithm($algorithm);

        return hash_hmac($normalizedAlgorithm, $timestamp . '.' . json_encode($payload, JSON_UNESCAPED_SLASHES), $secret);
    }
}
