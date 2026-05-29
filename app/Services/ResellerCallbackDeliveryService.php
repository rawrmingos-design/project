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
            'attempt_count' => 1,
            'status' => 'pending',
            'last_attempted_at' => now(),
        ]);

        $failureReason = ResellerCallbackUrlValidator::failureReason($delivery->callback_url, $context['environment']);

        if ($failureReason !== null) {
            $delivery->update([
                'status' => 'failed',
                'last_error' => $failureReason,
            ]);

            return ['status' => 'failed', 'reason' => $failureReason];
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Callback-Event' => $context['event_name'],
            'X-Callback-Version' => (string) max(1, (int) ($profile->version ?? 1)),
            'X-Callback-Timestamp' => (string) ($payload['timestamp'] ?? now()->toIso8601String()),
        ];

        $secret = $profile->decryptedWebhookSecret();

        if ($secret === '') {
            $delivery->update([
                'status' => 'failed',
                'last_error' => 'Webhook secret belum dikonfigurasi.',
            ]);

            return ['status' => 'failed', 'reason' => 'missing_secret'];
        }

        $headers[$this->resolveSignatureHeader($profile->signature_header)] = hash_hmac(
            $delivery->signature_algorithm,
            $rawPayload,
            $secret,
        );

        try {
            $response = Http::timeout($this->resolveTimeoutSeconds((int) ($profile->timeout_ms ?? 10000)))
                ->withHeaders($headers)
                ->withBody($rawPayload, 'application/json')
                ->post($delivery->callback_url);

            $delivery->last_response_status = $response->status();
            $delivery->last_response_body = Str::limit($response->body(), 2000);

            if ($response->successful()) {
                $delivery->status = 'delivered';
                $delivery->delivered_at = now();
                $delivery->last_error = null;
                $delivery->save();

                return ['status' => 'delivered', 'status_code' => $delivery->last_response_status];
            }

            $delivery->status = 'failed';
            $delivery->last_error = sprintf('HTTP %d response', $response->status());
            $delivery->save();

            return ['status' => 'failed', 'status_code' => $delivery->last_response_status];
        } catch (Throwable $exception) {
            $delivery->status = 'failed';
            $delivery->last_error = $exception->getMessage();
            $delivery->save();

            Log::error('Reseller outbound callback threw an exception.', [
                'order_id' => $pembelian->order_id,
                'reseller_integration_id' => $integration->getKey(),
                'message' => $exception->getMessage(),
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
            'event' => $context['event_name'],
            'timestamp' => now()->toIso8601String(),
            'invoiceNumber' => (string) $pembelian->order_id,
            'referenceNumber' => (string) ($payment?->reference ?? ''),
            'productName' => (string) $pembelian->layanan,
            'userData' => $this->buildUserData($pembelian),
            'statusCode' => PembelianStatus::apiStatusCode($pembelian->status),
            'statusLabel' => PembelianStatus::label($pembelian->status),
            'sn' => (string) ($pembelian->keterangan_sn ?? ''),
            'keteranganSn' => (string) ($pembelian->keterangan_sn ?? ''),
            'sandbox' => $context['environment'] === 'sandbox',
            'environment' => $context['environment'],
        ];
    }

    private function buildUserData(Pembelian $pembelian): string
    {
        $userData = trim((string) $pembelian->user_id);
        $zone = trim((string) $pembelian->zone);

        if ($zone !== '') {
            $userData .= '|' . $zone;
        }

        return $userData;
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
}
