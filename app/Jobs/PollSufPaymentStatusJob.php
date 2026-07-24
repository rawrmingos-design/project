<?php

namespace App\Jobs;

use App\Models\Pembelian;
use App\Services\ProviderRoutingService;
use App\Services\Providers\SufPaymentService;
use App\Services\ProviderStatusUpdateService;
use App\Support\PembelianStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollSufPaymentStatusJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor;

    public function __construct(
        public int $pembelianId,
        public ?string $providerOrderId = null,
        public int $attempt = 1,
    ) {
        $this->uniqueFor = max(180, self::intervalSeconds() + self::timeoutSeconds() + 30);
        $queue = self::queueName();

        if ($queue !== '') {
            $this->onQueue($queue);
        }
    }

    public static function dispatchIfNeeded(Pembelian $pembelian, ?string $providerOrderId = null, ?string $status = null): void
    {
        if (! self::isEnabled()) {
            return;
        }

        $providerCode = strtolower(trim((string) $pembelian->active_provider_code));
        if ($providerCode !== 'sufpayment') {
            return;
        }

        if (! $pembelian->relationLoaded('pembayaran')) {
            $pembelian->loadMissing('pembayaran');
        }

        if (! $pembelian->hasPaidPaymentStatus()) {
            return;
        }

        if (PembelianStatus::isFinal($pembelian->status)) {
            return;
        }

        $normalizedStatus = PembelianStatus::normalize($status ?? $pembelian->status);
        if (! in_array($normalizedStatus, [PembelianStatus::PENDING, PembelianStatus::PROCESSING, PembelianStatus::UNKNOWN], true)) {
            return;
        }

        $providerOrderId = trim((string) ($providerOrderId ?: $pembelian->provider_order_id ?: $pembelian->active_attempt_token));
        if ($providerOrderId === '') {
            return;
        }

        self::dispatch($pembelian->getKey(), $providerOrderId, 1)
            ->delay(now()->addSeconds(self::intervalSeconds()));
    }

    public function uniqueId(): string
    {
        return 'sufpayment-status:' . $this->pembelianId . ':' . trim((string) $this->providerOrderId);
    }

    public function handle(
        ProviderRoutingService $routingService,
        ProviderStatusUpdateService $statusUpdateService,
    ): void {
        $pembelian = Pembelian::query()
            ->with(['pembayaran', 'user', 'activeLayanan'])
            ->find($this->pembelianId);

        if (! $pembelian) {
            Log::debug('PollSufPaymentStatusJob: pembelian not found.', [
                'pembelian_id' => $this->pembelianId,
            ]);

            return;
        }

        if (strtolower(trim((string) $pembelian->active_provider_code)) !== 'sufpayment') {
            return;
        }

        if (! $pembelian->hasPaidPaymentStatus()) {
            return;
        }

        if (PembelianStatus::isFinal($pembelian->status)) {
            return;
        }

        $providerOrderId = trim((string) ($this->providerOrderId ?: $pembelian->provider_order_id ?: $pembelian->active_attempt_token));
        if ($providerOrderId === '') {
            $statusUpdateService->appendLog(
                $pembelian,
                'SufPayment polling skipped at ' . now()->format('Y-m-d H:i:s') . ': provider_order_id kosong.',
            );

            return;
        }

        $route = $routingService->resolveExplicitProvider('sufpayment', (string) $pembelian->active_provider_sku);
        $service = new SufPaymentService($route['credentials'] ?? []);
        $response = $service->status($providerOrderId);
        $providerResult = $this->normalizeProviderResult($response, $providerOrderId);

        if (($response['transport_error'] ?? false) === true || ! ($response['result'] ?? false)) {
            $statusUpdateService->appendLog(
                $pembelian,
                'SufPayment polling attempt ' . $this->attempt . ' failed at ' . now()->format('Y-m-d H:i:s') . ': ' . ($providerResult['message'] ?? 'Status check gagal.'),
            );
        } else {
            $statusUpdateService->apply($pembelian, $providerResult, 'sufpayment_polling');
        }

        $fresh = $pembelian->fresh(['pembayaran']);
        if (! $fresh || PembelianStatus::isFinal($fresh->status)) {
            return;
        }

        if ($this->attempt >= self::maxAttempts()) {
            $statusUpdateService->appendLog(
                $fresh,
                'SufPayment polling exhausted at ' . now()->format('Y-m-d H:i:s') . ': masih belum final setelah ' . $this->attempt . ' attempt.',
            );

            return;
        }

        self::dispatch($fresh->getKey(), $providerOrderId, $this->attempt + 1)
            ->delay(now()->addSeconds(self::intervalSeconds()));
    }

    private function normalizeProviderResult(array $response, string $fallbackId): array
    {
        if (($response['transport_error'] ?? false) === true || ! ($response['result'] ?? false)) {
            return [
                'success' => true,
                'order_status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
                'transaction_id' => $fallbackId,
                'provider_status' => PembelianStatus::PENDING,
                'message' => trim((string) ($response['message'] ?? 'SufPayment status check belum final.')),
                'raw' => $response,
            ];
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $statusMeta = SufPaymentService::normalizeStatusMeta($data['status'] ?? $data['order_status'] ?? null);
        $transactionId = $data['id']
            ?? $data['trxid']
            ?? $data['trx_id']
            ?? $data['transaction_id']
            ?? $fallbackId;
        $message = trim((string) ($data['message'] ?? $data['note'] ?? $data['msg'] ?? $response['message'] ?? ''));
        $sn = trim((string) ($data['sn'] ?? $data['serial_number'] ?? $data['note'] ?? ''));

        return [
            'success' => true,
            'order_status' => $statusMeta['internal_status'],
            'transaction_id' => $transactionId,
            'provider_status' => $statusMeta['raw_status'],
            'message' => $message !== '' ? $message : 'SufPayment status checked.',
            'sn' => $sn !== '' ? $sn : null,
            'raw' => $response,
        ];
    }

    private static function isEnabled(): bool
    {
        return filter_var(config('providers.sufpayment.polling.enabled', true), FILTER_VALIDATE_BOOL);
    }

    private static function intervalSeconds(): int
    {
        return max(30, (int) config('providers.sufpayment.polling.interval_seconds', 120));
    }

    private static function maxAttempts(): int
    {
        return max(1, (int) config('providers.sufpayment.polling.max_attempts', 30));
    }

    private static function timeoutSeconds(): int
    {
        return max(1, (int) config('providers.sufpayment.timeout', 15));
    }

    private static function queueName(): string
    {
        return trim((string) config('providers.sufpayment.polling.queue', 'default'));
    }
}
