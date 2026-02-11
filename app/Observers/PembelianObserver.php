<?php

namespace App\Observers;

use App\Models\Pembelian;
use App\Services\TierService;
use App\Services\AffiliateService;
use Illuminate\Support\Facades\Log;

class PembelianObserver
{
    protected $tierService;
    protected $affiliateService;

    public function __construct(TierService $tierService, AffiliateService $affiliateService)
    {
        $this->tierService = $tierService;
        $this->affiliateService = $affiliateService;
    }

    /**
     * Handle the Pembelian "updated" event.
     */
    public function updated(Pembelian $pembelian): void
    {
        // Check if status changed to Success
        if ($pembelian->wasChanged('status') && in_array($pembelian->status, ['Success', 'Sukses'])) {
            Log::info("PembelianObserver: Order {$pembelian->order_id} marked as Success. Checking Tier Upgrade & Affiliate Commission.");
            
            $user = $pembelian->user;
            if ($user) {
                // Check Tier
                $this->tierService->checkAndUpgradeTier($user);
                
                // Process Affiliate Commission
                $this->affiliateService->processCommission($pembelian);
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
            
            $user = $pembelian->user;
            if ($user) {
                $this->tierService->checkAndUpgradeTier($user);
                $this->affiliateService->processCommission($pembelian);
            }
        }
    }
}
