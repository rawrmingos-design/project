<?php

namespace App\Observers;

use App\Models\Pembelian;
use App\Events\TransactionSuccess;
use App\Services\TierService;
use App\Services\AffiliateService;
use App\Services\PointService;
use Illuminate\Support\Facades\Log;

class PembelianObserver
{
    protected $tierService;
    protected $affiliateService;
    protected $pointService;

    public function __construct(TierService $tierService, AffiliateService $affiliateService, PointService $pointService)
    {
        $this->tierService = $tierService;
        $this->affiliateService = $affiliateService;
        $this->pointService = $pointService;
    }

    /**
     * Handle the Pembelian "updated" event.
     */
    public function updated(Pembelian $pembelian): void
    {
        // Check if status changed to Success
        if ($pembelian->wasChanged('status') && in_array($pembelian->status, ['Success', 'Sukses'])) {
            Log::info("PembelianObserver: Order {$pembelian->order_id} marked as Success. Checking Tier Upgrade & Affiliate Commission.");

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
        // Check if status is Success
        if (in_array($pembelian->status, ['Success', 'Sukses'])) {
            Log::info("PembelianObserver: Order {$pembelian->order_id} created as Success. Checking Tier Upgrade & Affiliate Commission.");

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
}
