<?php

namespace App\Observers;

use App\Models\Pembelian;
use App\Events\TransactionSuccess;
use App\Services\TierService;
use App\Services\AffiliateService;
use App\Services\PointService;
use App\Services\ResellerCallbackDeliveryService;
use App\Services\ResetOutboundCallbackService;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\DB;

class PembelianObserver
{
    protected $tierService;
    protected $affiliateService;
    protected $pointService;
    protected $resetOutboundCallbackService;

    public function __construct(
        TierService $tierService,
        AffiliateService $affiliateService,
        PointService $pointService,
        ResetOutboundCallbackService $resetOutboundCallbackService
    )
    {
        $this->tierService = $tierService;
        $this->affiliateService = $affiliateService;
        $this->pointService = $pointService;
        $this->resetOutboundCallbackService = $resetOutboundCallbackService;
    }

    /**
     * Handle the Pembelian "updated" event.
     */
    public function updated(Pembelian $pembelian): void
    {
        if ($pembelian->wasChanged('status')) {
            $previousStatus = PembelianStatus::normalize($pembelian->getOriginal('status'));
            $currentStatus = PembelianStatus::normalize($pembelian->status);

            $this->syncResetLifecycleState($pembelian, $currentStatus);
            $this->dispatchResellerCallbackAfterCommit($pembelian, $previousStatus, $currentStatus);
            $this->dispatchResetCallbackAfterCommit($pembelian, $previousStatus, $currentStatus);
        }

        if ($pembelian->isSandboxOrder()) {
            return;
        }

        if ($pembelian->wasChanged('status') && $this->transitionedToSuccess($pembelian)) {
            $this->pointService->ensureRedeemedPointsForOrder($pembelian);
            
            $user = $pembelian->user;
            if ($user) {
                // Check Tier
                $this->tierService->checkAndUpgradeTier($user);
                
                // Process Affiliate Commission
                $this->affiliateService->processCommission($pembelian);

                // Award points
                TransactionSuccess::dispatch($pembelian, $user);
            }
        }
    }

    /**
     * Handle the Pembelian "created" event.
     */
    public function created(Pembelian $pembelian): void
    {
        $this->dispatchInitialResellerCallbackAfterCommit($pembelian);

        if ($pembelian->isSandboxOrder()) {
            return;
        }

        if (PembelianStatus::normalize($pembelian->status) === PembelianStatus::SUCCESS) {
            $this->pointService->ensureRedeemedPointsForOrder($pembelian);
            
            $user = $pembelian->user;
            if ($user) {
                $this->tierService->checkAndUpgradeTier($user);
                $this->affiliateService->processCommission($pembelian);

                // Award points
                TransactionSuccess::dispatch($pembelian, $user);
            }
        }
    }

    private function transitionedToSuccess(Pembelian $pembelian): bool
    {
        return PembelianStatus::normalize($pembelian->status) === PembelianStatus::SUCCESS
            && PembelianStatus::normalize($pembelian->getOriginal('status')) !== PembelianStatus::SUCCESS;
    }

    private function dispatchResetCallbackAfterCommit(
        Pembelian $pembelian,
        string $previousStatus,
        string $currentStatus,
    ): void
    {
        if ((int) ($pembelian->invoice_version ?? 0) <= 0) {
            return;
        }

        if ($previousStatus === $currentStatus) {
            return;
        }

        $pembelianId = $pembelian->getKey();

        DB::afterCommit(function () use ($pembelianId, $previousStatus, $currentStatus): void {
            $freshPembelian = Pembelian::query()
                ->with(['user', 'activeLayanan', 'pembayaran'])
                ->find($pembelianId);

            if (!$freshPembelian) {
                return;
            }

            $this->resetOutboundCallbackService->dispatchForStatusTransition(
                $freshPembelian,
                $previousStatus,
                $currentStatus,
            );
        });
    }

    private function dispatchInitialResellerCallbackAfterCommit(Pembelian $pembelian): void
    {
        if ($pembelian->reseller_integration_id === null) {
            return;
        }

        $pembelianId = $pembelian->getKey();

        DB::afterCommit(function () use ($pembelianId): void {
            $freshPembelian = Pembelian::query()
                ->with(['user', 'pembayaran', 'resellerIntegration.callbackProfile'])
                ->find($pembelianId);

            if (! $freshPembelian) {
                return;
            }

            app(ResellerCallbackDeliveryService::class)->dispatchInitial($freshPembelian);
        });
    }

    private function dispatchResellerCallbackAfterCommit(
        Pembelian $pembelian,
        string $previousStatus,
        string $currentStatus,
    ): void
    {
        if ($pembelian->reseller_integration_id === null) {
            return;
        }

        $pembelianId = $pembelian->getKey();

        DB::afterCommit(function () use ($pembelianId, $previousStatus, $currentStatus): void {
            $freshPembelian = Pembelian::query()
                ->with(['user', 'pembayaran', 'resellerIntegration.callbackProfile'])
                ->find($pembelianId);

            if (! $freshPembelian) {
                return;
            }

            app(ResellerCallbackDeliveryService::class)->dispatchFinalStatusTransition(
                $freshPembelian,
                $previousStatus,
                $currentStatus,
            );
        });
    }

    private function syncResetLifecycleState(Pembelian $pembelian, string $currentStatus): void
    {
        if ((int) ($pembelian->invoice_version ?? 0) <= 0) {
            return;
        }

        $nextResetStatus = match ($currentStatus) {
            PembelianStatus::SUCCESS => 'completed',
            PembelianStatus::FAILED => 'failed',
            PembelianStatus::CANCELLED,
            PembelianStatus::EXPIRED,
            PembelianStatus::REFUNDED => 'cancelled',
            PembelianStatus::PENDING,
            PembelianStatus::PROCESSING => 'processing',
            default => $pembelian->normalizedResetStatus(),
        };

        if ($pembelian->normalizedResetStatus() === $nextResetStatus) {
            return;
        }

        $pembelian->forceFill([
            'reset_status' => $nextResetStatus,
        ])->saveQuietly();
    }
}
