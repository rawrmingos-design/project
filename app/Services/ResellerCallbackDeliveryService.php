<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\ResellerCallbackDelivery;
use App\Support\PembelianStatus;
use App\Support\ResellerCallbackUrlValidator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ResellerCallbackDeliveryService
{
    public const LIVE_EVENT_NAME = 'h2h.order.updated';
    public const SANDBOX_EVENT_NAME = 'h2h.sandbox.order.updated';

    public function dispatchInitial(Pembelian $pembelian): array
    {
        if (! $this->shouldDispatchForOrder($pembelian)) {
            return ['status' => 'skipped', 'reason' => 'not_h2h_live_order'];
        }

        return $this->deliver($pembelian);
    }

    public function dispatchFinalStatusTransition(Pembelian $pembelian, string $previousStatus, string $currentStatus): array
    {
        if (! $this->shouldDispatchForOrder($pembelian)) {
            return ['status' => 'skipped', 'reason' => 'not_h2h_live_order'];
        }

        if ($previousStatus === $currentStatus || ! PembelianStatus::isFinal($currentStatus)) {
            return ['status' => 'skipped', 'reason' => 'status_not_dispatchable'];
        }

        return $this->deliver($pembelian);
    }

    /**
     * Manually resend an existing failed callback delivery.
     *
     * Guards:
     *   - Only 'failed' deliveries can be resent (not 'delivered' or 'pending')
     *   - Max 3 manual resend attempts (attempt_count tracked cumulatively)
     *   - Pessimistic lock on the delivery row to prevent concurrent double-resend
     *
     * On success, updates the existing delivery record (no new record created).
     */
    public function resend(ResellerCallbackDelivery $delivery): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($delivery) {
            // Re-fetch with lock to prevent concurrent duplicate resends
            /** @var ResellerCallbackDelivery $locked */
            $locked = ResellerCallbackDelivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->getKey());

            if ($locked->status === 'delivered') {
                return ['status' => 'skipped', 'reason' => 'already_delivered'];
            }

            // Max 3 additional resend attempts (original attempt_count = 1)
            // So total allowed = 4 (1 initial + 3 manual resends)
            if ($locked->attempt_count >= 4) {
                return ['status' => 'rejected', 'reason' => 'max_retries_exceeded'];
            }

            // Increment attempt counter before sending
            $locked->increment('attempt_count');
            $locked->last_attempted_at = now();
            $locked->save();

            // Reload relations needed by the send flow
            $locked->loadMissing([
                'callbackProfile',
                'pembelian.resellerIntegration.callbackProfile',
                'pembelian.pembayaran',
            ]);

            return $this->redeliver($locked);
        });
    }

    private function deliver(Pembelian $pembelian): array
    {
        $pembelian->loadMissing([
            'user',
            'pembayaran',
            'resellerIntegration.user',
            'resellerIntegration.callbackProfile',
        ]);

        $integration = $pembelian->resellerIntegration;
        $profile = $integration?->callbackProfile;
        $context = $this->resolveDeliveryContext($pembelian, $integration);

        if (! $integration || ! $profile || ! $profile->is_enabled) {
            Log::info('Reseller outbound callback skipped because callback profile is incomplete.', [
                'order_id' => $pembelian->order_id,
                'reseller_integration_id' => $pembelian->reseller_integration_id,
            ]);

            return ['status' => 'skipped', 'reason' => 'callback_not_configured'];
        }

        $payload = $this->buildPayload($pembelian);
        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($rawPayload === false) {
            return ['status' => 'failed', 'reason' => 'payload_encoding_failed'];
        }

        $delivery = ResellerCallbackDelivery::query()->create([
            'user_id' => $integration->user_id,
            'reseller_integration_id' => $integration->getKey(),
            'reseller_callback_profile_id' => $profile->getKey(),
            'pembelian_id' => $pembelian->getKey(),
            'environment' => $context['environment'],
            'event_name' => $context['event_name'],
            'order_id' => (string) $pembelian->order_id,
            'reference_number' => (string) ($pembelian->pembayaran?->reference ?? ''),
            'callback_url' => (string) $profile->callback_url,
            'signature_algorithm' => $this->resolveSigningAlgorithm($profile->signing_algorithm),
            'payload' => $payload,
            'attempt_count' => 0,
            'status' => 'pending',
            'last_attempted_at' => null,
        ]);

        $failureReason = ResellerCallbackUrlValidator::failureReason($delivery->callback_url, $context['environment']);

        if ($failureReason !== null) {
            $delivery->update([
                'status' => 'failed',
                'last_error' => $failureReason,
            ]);

            return ['status' => 'failed', 'reason' => $failureReason];
        }

        // Dispatch background job for async webhook delivery with exponential backoff
        \App\Jobs\DeliverResellerWebhookJob::dispatch($delivery)->onQueue('webhook');

        return ['status' => 'pending', 'reason' => 'dispatched_to_queue'];
    }

    /**
     * Re-execute HTTP send for an existing delivery record.
     *
     * Unlike deliver(), this method does NOT create a new record.
     * It uses the payload, callback_url, and signature already stored
     * on the delivery row — rebuilt fresh for the resend attempt.
     */
    public function redeliver(ResellerCallbackDelivery $delivery): array
    {
        $profile  = $delivery->callbackProfile;
        $pembelian = $delivery->pembelian;

        if (! $profile || ! $profile->is_enabled) {
            $delivery->update(['status' => 'failed', 'last_error' => 'Callback profile disabled or missing.']);
            return ['status' => 'failed', 'reason' => 'callback_not_configured'];
        }

        // Rebuild payload fresh (status may have changed since original delivery)
        $payload    = $this->buildPayload($pembelian);
        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($rawPayload === false) {
            $delivery->update(['status' => 'failed', 'last_error' => 'Payload encoding failed.']);
            return ['status' => 'failed', 'reason' => 'payload_encoding_failed'];
        }

        // Update stored payload to reflect freshly rebuilt data
        $delivery->payload = $payload;
        $delivery->save();

        $secret = $profile->decryptedWebhookSecret();

        if ($secret === '') {
            $delivery->update(['status' => 'failed', 'last_error' => 'Webhook secret belum dikonfigurasi.']);
            return ['status' => 'failed', 'reason' => 'missing_secret'];
        }

        $context = [
            'event_name' => $delivery->event_name,
        ];

        $headers = [
            'Accept'              => 'application/json',
            'Content-Type'        => 'application/json',
            'X-Callback-Event'    => $context['event_name'],
            'X-Callback-Version'  => (string) max(1, (int) ($profile->version ?? 1)),
            'X-Callback-Timestamp'=> (string) ($payload['timestamp'] ?? now()->toIso8601String()),
        ];

        $headers[$this->resolveSignatureHeader($profile->signature_header)] = hash_hmac(
            $this->resolveSigningAlgorithm($profile->signing_algorithm),
            $rawPayload,
            $secret,
        );

        try {
            $response = Http::timeout($this->resolveTimeoutSeconds((int) ($profile->timeout_ms ?? 10000)))
                ->withHeaders($headers)
                ->withBody($rawPayload, 'application/json')
                ->post($delivery->callback_url);

            $delivery->last_response_status = $response->status();
            $delivery->last_response_body   = Str::limit($response->body(), 2000);

            if ($response->successful()) {
                $delivery->status      = 'delivered';
                $delivery->delivered_at = now();
                $delivery->last_error  = null;
                $delivery->save();

                return ['status' => 'delivered', 'status_code' => $delivery->last_response_status];
            }

            $delivery->status     = 'failed';
            $delivery->last_error = sprintf('HTTP %d response', $response->status());
            $delivery->save();

            return ['status' => 'failed', 'status_code' => $delivery->last_response_status];

        } catch (Throwable $exception) {
            $delivery->status     = 'failed';
            $delivery->last_error = $exception->getMessage();
            $delivery->save();

            Log::error('Reseller callback resend threw an exception.', [
                'delivery_id' => $delivery->getKey(),
                'order_id'    => $pembelian?->order_id,
                'message'     => $exception->getMessage(),
            ]);

            return ['status' => 'failed', 'reason' => $exception->getMessage()];
        }
    }

    private function shouldDispatchForOrder(Pembelian $pembelian): bool
    {
        return $pembelian->reseller_integration_id !== null
            && (
                strtolower(trim((string) $pembelian->traffic_source)) === 'reseller_h2h'
                || $pembelian->isSandboxOrder()
            );
    }

    private function buildPayload(Pembelian $pembelian): array
    {
        $payment = $pembelian->pembayaran;
        $context = $this->resolveDeliveryContext($pembelian, $pembelian->resellerIntegration);

        return [
            'event'           => $context['event_name'],
            'timestamp'       => now()->toIso8601String(),
            'invoiceNumber'   => (string) $pembelian->order_id,
            'referenceNumber' => (string) ($payment?->reference ?? ''),
            'code'            => (string) ($pembelian->active_provider_sku ?? ''),
            'productName'     => (string) $pembelian->layanan,
            'userId'          => (string) ($pembelian->user_id ?? ''),
            'zoneId'          => (string) ($pembelian->zone ?? ''),
            'statusCode'      => PembelianStatus::apiStatusCode($pembelian->status),
            'statusLabel'     => PembelianStatus::label($pembelian->status),
            'sn'              => (string) ($pembelian->keterangan_sn ?? ''),
            'keteranganSn'    => (string) ($pembelian->keterangan_sn ?? ''),
            'sandbox'         => $context['environment'] === 'sandbox',
            'environment'     => $context['environment'],
        ];
    }



    private function resolveDeliveryContext(Pembelian $pembelian, $integration): array
    {
        $environment = $pembelian->isSandboxOrder() || strtolower(trim((string) ($integration?->mode ?? ''))) === 'sandbox'
            ? 'sandbox'
            : 'live';

        return [
            'environment' => $environment,
            'event_name' => $environment === 'sandbox'
                ? self::SANDBOX_EVENT_NAME
                : self::LIVE_EVENT_NAME,
        ];
    }

    private function resolveSigningAlgorithm(?string $algorithm): string
    {
        $algorithm = strtolower(trim((string) $algorithm));

        return in_array($algorithm, ['sha1', 'sha256', 'sha512'], true) ? $algorithm : 'sha256';
    }

    private function resolveSignatureHeader(?string $header): string
    {
        $header = trim((string) $header);

        return $header !== '' ? $header : 'X-Callback-Signature';
    }

    private function resolveTimeoutSeconds(int $timeoutMs): int
    {
        return max(1, (int) ceil(max(1000, $timeoutMs) / 1000));
    }

    /**
     * Phase 5 — Task 5.3
     * Send a synthetic test webhook to a callback profile WITHOUT a real order.
     * Creates a ResellerCallbackDelivery record with event_name = 'h2h.webhook.test'.
     *
     * @param  \App\Models\ResellerCallbackProfile  $profile
     * @param  array<string, mixed>                 $testPayload
     * @return array{success: bool, reason?: string}
     */
    public function sendTestWebhook(\App\Models\ResellerCallbackProfile $profile, array $testPayload): array
    {
        $profile->loadMissing('integration');

        $urlFailure = ResellerCallbackUrlValidator::failureReason(
            (string) $profile->callback_url,
            'sandbox'
        );

        if ($urlFailure !== null) {
            return ['success' => false, 'reason' => $urlFailure];
        }

        $secret = $profile->decryptedWebhookSecret();

        if ($secret === '') {
            return ['success' => false, 'reason' => 'Webhook secret belum dikonfigurasi.'];
        }

        $rawPayload = json_encode($testPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($rawPayload === false) {
            return ['success' => false, 'reason' => 'Payload encoding failed.'];
        }

        $sigHeader = $this->resolveSignatureHeader($profile->signature_header ?? null);
        $algorithm = $this->resolveSigningAlgorithm($profile->signing_algorithm ?? null);
        $signature = hash_hmac($algorithm, $rawPayload, $secret);

        $headers = [
            'Accept'               => 'application/json',
            'Content-Type'         => 'application/json',
            'X-Callback-Event'     => 'h2h.webhook.test',
            'X-Callback-Version'   => (string) max(1, (int) ($profile->version ?? 1)),
            'X-Callback-Timestamp' => $testPayload['timestamp'] ?? now()->toIso8601String(),
            $sigHeader             => $signature,
        ];

        // Create delivery record before sending (for log visibility)
        $delivery = ResellerCallbackDelivery::query()->create([
            'user_id'                     => $profile->integration?->user_id,
            'reseller_integration_id'     => $profile->reseller_integration_id,
            'reseller_callback_profile_id'=> $profile->getKey(),
            'pembelian_id'                => null,  // No real order
            'environment'                 => 'sandbox',
            'event_name'                  => 'h2h.webhook.test',
            'order_id'                    => $testPayload['invoiceNumber'] ?? 'TEST',
            'reference_number'            => $testPayload['referenceNumber'] ?? '',
            'callback_url'                => (string) $profile->callback_url,
            'signature_algorithm'         => $algorithm,
            'payload'                     => $testPayload,
            'attempt_count'               => 1,
            'status'                      => 'pending',
            'last_attempted_at'           => now(),
        ]);

        try {
            $response = Http::timeout($this->resolveTimeoutSeconds((int) ($profile->timeout_ms ?? 10000)))
                ->withHeaders($headers)
                ->withBody($rawPayload, 'application/json')
                ->post($delivery->callback_url);

            $delivery->last_response_status = $response->status();
            $delivery->last_response_body   = Str::limit($response->body(), 2000);

            if ($response->successful()) {
                $delivery->status       = 'delivered';
                $delivery->delivered_at = now();
                $delivery->last_error   = null;
                $delivery->save();

                return ['success' => true];
            }

            $delivery->status     = 'failed';
            $delivery->last_error = sprintf('HTTP %d response', $response->status());
            $delivery->save();

            return ['success' => false, 'reason' => sprintf('HTTP %d response dari callback URL.', $response->status())];
        } catch (Throwable $exception) {
            $delivery->status     = 'failed';
            $delivery->last_error = $exception->getMessage();
            $delivery->save();

            Log::warning('Reseller test webhook delivery failed.', [
                'reseller_integration_id' => $profile->reseller_integration_id,
                'callback_url'            => $delivery->callback_url,
                'message'                 => $exception->getMessage(),
            ]);

            return ['success' => false, 'reason' => $exception->getMessage()];
        }
    }
}

