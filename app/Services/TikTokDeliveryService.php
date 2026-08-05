<?php

namespace App\Services;

use App\Jobs\SendTikTokConversionJob;
use App\Models\Pembelian;
use App\Models\TikTokConversionDelivery;
use App\Support\PembelianStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class TikTokDeliveryService
{
    private const ENDPOINT = 'https://business-api.tiktok.com/open_api/v1.3/event/track/';
    private const NON_WEB_TRAFFIC_SOURCES = ['api_v2', 'whatsapp_gateway', 'telegram_gateway', 'reseller_h2h'];

    public function __construct(
        private readonly TikTokSettingsService $tiktokSettings,
    ) {
    }

    public function dispatchForEligibleOrder(Pembelian $pembelian): bool
    {
        $pembelian->loadMissing(['pembayaran', 'user']);

        if (! $this->isEligible($pembelian)) {
            return false;
        }

        $pixelId = trim((string) ($this->tiktokSettings->pixelId() ?? ''));
        $eventId = $pembelian->deriveDisplayInvoiceId();

        try {
            $delivery = TikTokConversionDelivery::query()->createOrFirst(
                [
                    'pixel_id' => $pixelId,
                    'event_name' => 'CompletePayment',
                    'event_id' => $eventId,
                ],
                [
                    'pembelian_id' => $pembelian->getKey(),
                    'delivery_status' => 'pending',
                ],
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return false;
        }

        if (! $delivery->wasRecentlyCreated) {
            return false;
        }

        DB::afterCommit(static function () use ($delivery): void {
            SendTikTokConversionJob::dispatch($delivery->getKey());
        });

        return true;
    }

    public function executeDelivery(int $deliveryId): void
    {
        $delivery = TikTokConversionDelivery::query()->find($deliveryId);

        if (! $delivery || in_array($delivery->delivery_status, ['delivered', 'failed'], true)) {
            return;
        }

        $pembelian = Pembelian::query()
            ->withoutGlobalScope('tenant')
            ->with(['pembayaran', 'user'])
            ->find($delivery->pembelian_id);

        if (! $pembelian || ! $this->isEligible($pembelian)) {
            $delivery->forceFill([
                'delivery_status' => 'failed',
                'last_error' => 'Order no longer exists or is no longer eligible.',
            ])->save();

            return;
        }

        $configuredPixelId = trim((string) ($this->tiktokSettings->pixelId() ?? ''));
        if (! $this->tiktokSettings->enabled() || $configuredPixelId === '' || ! hash_equals($delivery->pixel_id, $configuredPixelId)) {
            $delivery->forceFill([
                'delivery_status' => 'failed',
                'last_error' => 'TikTok tracking disabled or delivery Pixel ID no longer matches configuration.',
            ])->save();

            return;
        }

        $accessToken = trim((string) ($this->tiktokSettings->accessToken() ?? ''));

        if ($accessToken === '') {
            $delivery->forceFill([
                'delivery_status' => 'failed',
                'last_error' => 'Missing TikTok Access Token configuration.',
            ])->save();

            return;
        }

        $delivery->increment('attempts');
        $delivery->refresh();

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->withHeaders(['Access-Token' => $accessToken])
                ->timeout(10)
                ->post(self::ENDPOINT, $this->buildPayload($delivery, $pembelian));
        } catch (ConnectionException $exception) {
            $delivery->forceFill([
                'delivery_status' => 'ambiguous',
                'last_error' => Str::limit('Connection error: ' . $exception->getMessage(), 2000),
            ])->save();

            throw $exception;
        }

        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $apiCode = $body['code'] ?? null;

        if ($response->status() >= 200 && $response->status() < 400 && $apiCode === 0) {
            $delivery->forceFill([
                'delivery_status' => 'delivered',
                'last_error' => null,
            ])->save();

            return;
        }

        $error = Str::limit(sprintf(
            'HTTP %d, API code %s: %s',
            $response->status(),
            $apiCode === null ? 'missing' : (string) $apiCode,
            json_encode($body, JSON_UNESCAPED_SLASHES),
        ), 2000);

        if ($this->isRetryableResponse($response->status(), $apiCode)) {
            $delivery->forceFill([
                'delivery_status' => 'pending',
                'last_error' => $error,
            ])->save();

            throw new RuntimeException($error);
        }

        $delivery->forceFill([
            'delivery_status' => 'failed',
            'last_error' => $error,
        ])->save();
    }

    public function markPermanentlyFailed(int $deliveryId, ?\Throwable $exception): void
    {
        TikTokConversionDelivery::query()
            ->whereKey($deliveryId)
            ->whereNotIn('delivery_status', ['delivered', 'failed'])
            ->update([
                'delivery_status' => 'failed',
                'last_error' => Str::limit(
                    $exception?->getMessage() ?: 'Queue attempts exhausted.',
                    2000,
                ),
                'updated_at' => now(),
            ]);
    }

    private function isEligible(Pembelian $pembelian): bool
    {
        if (! $this->tiktokSettings->enabled() || blank($this->tiktokSettings->pixelId())) {
            return false;
        }

        if ($pembelian->tenant_id !== null || $pembelian->reseller_integration_id !== null) {
            return false;
        }

        if ($pembelian->isSandboxOrder()) {
            return false;
        }

        if (PembelianStatus::normalize($pembelian->status) !== PembelianStatus::SUCCESS) {
            return false;
        }

        if (! $pembelian->hasPaidPaymentStatus()) {
            return false;
        }

        $trafficSource = strtolower(trim((string) $pembelian->traffic_source));

        return ! in_array($trafficSource, self::NON_WEB_TRAFFIC_SOURCES, true);
    }

    private function buildPayload(TikTokConversionDelivery $delivery, Pembelian $pembelian): array
    {
        $payment = $pembelian->pembayaran;
        $amount = (int) ($payment?->harga ?? $pembelian->harga ?? 0);
        $eventTime = ($payment?->paid_at ?? $payment?->updated_at ?? $pembelian->updated_at ?? now())->timestamp;
        $externalId = null;

        if ($pembelian->user && ! in_array(strtolower(trim((string) $pembelian->username)), ['', 'anonim', 'guest'], true)) {
            $externalId = hash('sha256', strtolower(trim((string) $pembelian->user->getKey())));
        }

        $payload = [
            'event_source' => 'web',
            'event_source_id' => $delivery->pixel_id,
            'data' => [[
                'event' => 'CompletePayment',
                'event_time' => $eventTime,
                'event_id' => $delivery->event_id,
                'user' => array_filter([
                    'email' => $this->hashEmail($pembelian->email_pembeli ?? $pembelian->user?->email),
                    'phone' => $this->hashPhone($payment?->no_pembeli ?? $pembelian->user?->no_wa),
                    'external_id' => $externalId,
                    'ttclid' => $pembelian->ttclid,
                    'ttp' => $pembelian->ttp,
                    'ip' => $pembelian->ip_address,
                    'user_agent' => $pembelian->client_user_agent,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''),
                'page' => [
                    'url' => route('pembelian', $pembelian->order_id),
                ],
                'properties' => [
                    'contents' => [[
                        'content_id' => (string) ($pembelian->active_provider_sku ?: $pembelian->active_layanan_id ?: $pembelian->order_id),
                        'content_type' => 'product',
                        'content_name' => (string) $pembelian->layanan,
                        'quantity' => 1,
                        'price' => $amount,
                    ]],
                    'value' => $amount,
                    'currency' => 'IDR',
                ],
            ]],
        ];

        $testEventCode = trim((string) ($this->tiktokSettings->testEventCode() ?? ''));
        if ($testEventCode !== '') {
            $payload['test_event_code'] = $testEventCode;
        }

        return $payload;
    }

    private function isRetryableResponse(int $status, mixed $apiCode): bool
    {
        if ($status === 429 || $status >= 500 || $status < 200) {
            return true;
        }

        return $status >= 200 && $status < 400 && $apiCode !== 0;
    }

    private function hashEmail(?string $email): ?string
    {
        $cleaned = strtolower(trim((string) $email));

        return filter_var($cleaned, FILTER_VALIDATE_EMAIL)
            ? hash('sha256', $cleaned)
            : null;
    }

    private function hashPhone(?string $phone): ?string
    {
        if (blank($phone) || $phone === '-') {
            return null;
        }

        $digits = preg_replace('/\D/', '', (string) $phone);

        if ($digits === null || $digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '62' . $digits;
        }

        if (! str_starts_with($digits, '62')) {
            return null;
        }

        return hash('sha256', '+' . $digits);
    }
}
