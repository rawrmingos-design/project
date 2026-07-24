<?php

namespace App\Jobs;

use App\Models\Pembelian;
use App\Services\OrderProcessingService;
use App\Services\ProviderStatusUpdateService;
use App\Support\PembelianStatus;
use App\Support\ProviderDispatchTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPembelianToProviderJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 120;

    public function __construct(
        public int $pembelianId,
        public ?int $requestedBy = null,
        public string $dispatchMode = 'auto',
    ) {
    }

    public function uniqueId(): string
    {
        return 'send-pembelian-to-provider:' . $this->pembelianId;
    }

    public function handle(
        OrderProcessingService $orderProcessingService,
        ?ProviderStatusUpdateService $statusUpdateService = null,
    ): void {
        $statusUpdateService ??= app(ProviderStatusUpdateService::class);
        ProviderDispatchTracker::markProcessing($this->pembelianId);

        $pembelian = Pembelian::query()->find($this->pembelianId);

        if (! $pembelian) {
            Log::debug('SendPembelianToProviderJob: pembelian not found.', [
                'pembelian_id' => $this->pembelianId,
            ]);

            ProviderDispatchTracker::clear($this->pembelianId);
            return;
        }

        try {
            $result = $orderProcessingService->process($pembelian, $this->dispatchMode);
            $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::UNKNOWN);

            if (! ($result['success'] ?? false) && ! in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
                $statusUpdateService->appendLog(
                    $pembelian,
                    'Queued provider dispatch failed at ' . now()->format('Y-m-d H:i:s') . ': ' . ($result['message'] ?? 'Unknown error'),
                );

                ProviderDispatchTracker::clear($this->pembelianId);
                return;
            }

            $result['order_status'] = match ($normalizedStatus) {
                PembelianStatus::SUCCESS => PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS),
                PembelianStatus::PENDING => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
                PembelianStatus::PROCESSING => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PROCESSING),
                PembelianStatus::FAILED, PembelianStatus::CANCELLED => PembelianStatus::preferredDatabaseLabel($normalizedStatus),
                default => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
            };

            $statusUpdateService->apply($pembelian, $result, 'queued_provider_dispatch');

            $fresh = $pembelian->fresh(['pembayaran']);
            if ($fresh) {
                PollSufPaymentStatusJob::dispatchIfNeeded(
                    $fresh,
                    $result['transaction_id'] ?? null,
                    $result['order_status'] ?? null,
                );
            }

            ProviderDispatchTracker::clear($this->pembelianId);
        } catch (\Throwable $exception) {
            Log::error('SendPembelianToProviderJob failed.', [
                'pembelian_id' => $this->pembelianId,
                'order_id' => $pembelian->order_id,
                'display_order_id' => $pembelian->display_order_id,
                'active_attempt_reference' => $pembelian->active_attempt_reference,
                'active_provider_code' => $pembelian->active_provider_code,
                'active_provider_sku' => $pembelian->active_provider_sku,
                'dispatch_mode' => $this->dispatchMode,
                'message' => $exception->getMessage(),
            ]);

            $statusUpdateService->appendLog(
                $pembelian,
                'Queued provider dispatch exception at ' . now()->format('Y-m-d H:i:s') . ': ' . $exception->getMessage(),
            );

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        unset($exception);

        ProviderDispatchTracker::clear($this->pembelianId);
    }
}
