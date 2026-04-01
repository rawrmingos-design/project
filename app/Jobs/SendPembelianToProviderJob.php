<?php

namespace App\Jobs;

use App\Models\Pembelian;
use App\Services\OrderProcessingService;
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
    ) {
    }

    public function uniqueId(): string
    {
        return 'send-pembelian-to-provider:' . $this->pembelianId;
    }

    public function handle(OrderProcessingService $orderProcessingService): void
    {
        ProviderDispatchTracker::markProcessing($this->pembelianId);

        $pembelian = Pembelian::query()->find($this->pembelianId);

        if (! $pembelian) {
            Log::warning('SendPembelianToProviderJob: pembelian not found.', [
                'pembelian_id' => $this->pembelianId,
            ]);

            ProviderDispatchTracker::clear($this->pembelianId);
            return;
        }

        try {
            $result = $orderProcessingService->process($pembelian);
            $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::UNKNOWN);

            if (! ($result['success'] ?? false)) {
                if (in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
                    $pembelian->update([
                        'provider_order_id' => $result['transaction_id'] ?? $pembelian->provider_order_id,
                        'status' => PembelianStatus::preferredDatabaseLabel($normalizedStatus),
                        'keterangan_sn' => trim((string) ($result['sn'] ?? '')) ?: (trim((string) ($result['message'] ?? '')) ?: $pembelian->keterangan_sn),
                        'log' => $this->appendBoundedLog(
                            $pembelian->log,
                            'Queued provider dispatch final failure at ' . now()->format('Y-m-d H:i:s') . ': ' . ($result['message'] ?? 'Provider returned failed status'),
                        ),
                        'reset_status' => $pembelian->invoice_version > 0 ? 'failed' : $pembelian->reset_status,
                    ]);

                    ProviderDispatchTracker::clear($this->pembelianId);
                    return;
                }

                $pembelian->update([
                    'log' => $this->appendBoundedLog(
                        $pembelian->log,
                        'Queued provider dispatch failed at ' . now()->format('Y-m-d H:i:s') . ': ' . ($result['message'] ?? 'Unknown error'),
                    ),
                ]);

                ProviderDispatchTracker::clear($this->pembelianId);
                return;
            }

            $nextStatus = match ($normalizedStatus) {
                PembelianStatus::SUCCESS => PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS),
                PembelianStatus::PENDING => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
                PembelianStatus::PROCESSING => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PROCESSING),
                PembelianStatus::FAILED, PembelianStatus::CANCELLED => PembelianStatus::preferredDatabaseLabel($normalizedStatus),
                default => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
            };

            $nextResetStatus = $pembelian->reset_status;
            if ($pembelian->invoice_version > 0) {
                $nextResetStatus = match ($normalizedStatus) {
                    PembelianStatus::SUCCESS => 'completed',
                    PembelianStatus::FAILED, PembelianStatus::CANCELLED => 'failed',
                    default => 'processing',
                };
            }

            $pembelian->update([
                'provider_order_id' => $result['transaction_id'] ?? $pembelian->provider_order_id,
                'status' => $nextStatus,
                'keterangan_sn' => trim((string) ($result['sn'] ?? '')) ?: (in_array($normalizedStatus, [PembelianStatus::PENDING, PembelianStatus::PROCESSING], true) ? 'Sedang Diproses' : $pembelian->keterangan_sn),
                'log' => $this->appendBoundedLog(
                    $pembelian->log,
                    'Queued provider dispatch at ' . now()->format('Y-m-d H:i:s') . ': ' . ($result['message'] ?? 'Order dispatched'),
                ),
                'reset_status' => $nextResetStatus,
            ]);
            ProviderDispatchTracker::clear($this->pembelianId);
        } catch (\Throwable $exception) {
            Log::error('SendPembelianToProviderJob failed.', [
                'pembelian_id' => $this->pembelianId,
                'order_id' => $pembelian->order_id,
                'display_order_id' => $pembelian->display_order_id,
                'active_attempt_reference' => $pembelian->active_attempt_reference,
                'active_provider_code' => $pembelian->active_provider_code,
                'active_provider_sku' => $pembelian->active_provider_sku,
                'message' => $exception->getMessage(),
            ]);

            $pembelian->update([
                'log' => $this->appendBoundedLog(
                    $pembelian->log,
                    'Queued provider dispatch exception at ' . now()->format('Y-m-d H:i:s') . ': ' . $exception->getMessage(),
                ),
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        ProviderDispatchTracker::clear($this->pembelianId);
    }

    private function appendBoundedLog(?string $existingLog, string $entry, int $limit = 1000): string
    {
        $existingLog = trim((string) $existingLog);
        $entry = trim($entry);

        $combined = $existingLog !== ''
            ? $existingLog . PHP_EOL . $entry
            : $entry;

        if (mb_strlen($combined) <= $limit) {
            return $combined;
        }

        return mb_substr($combined, -$limit);
    }
}
