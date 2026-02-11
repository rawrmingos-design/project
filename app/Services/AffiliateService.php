<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\User;
use App\Models\SettingWeb;
use App\Models\AffiliateHistory;
use Illuminate\Support\Facades\Log;

class AffiliateService
{
    public function processCommission(Pembelian $pembelian)
    {
        // 1. Check if status is Success
        if (!in_array($pembelian->status, ['Success', 'Sukses'])) {
            return;
        }

        // 2. Check if user exists and has uplink
        $user = $pembelian->user;
        if (!$user || !$user->uplink) {
            return;
        }

        // 3. Find Uplink User
        // Uplink is stored as username based on migration/register logic
        $uplinkUser = User::where('username', $user->uplink)->first();
        if (!$uplinkUser) {
            return;
        }

        // 4. Idempotency Check: Don't pay commission twice for same order
        if (AffiliateHistory::where('order_id', $pembelian->order_id)->exists()) {
            return;
        }

        // 5. Calculate Commission
        $setting = SettingWeb::first();
        $percent = $setting->commission_percent ?? 20; // Default 20%
        $profit = $pembelian->profit;
        
        if ($profit <= 0) {
            return; // No profit, no commission
        }

        $commissionAmount = intval($profit * ($percent / 100));

        if ($commissionAmount <= 0) {
            return;
        }

        // 6. Distribute Commission
        try {
            $uplinkUser->balance += $commissionAmount;
            $uplinkUser->save();

            // 7. Log History
            AffiliateHistory::create([
                'uplink_id' => $uplinkUser->id,
                'downlink_id' => $user->id,
                'order_id' => $pembelian->order_id,
                'amount' => $commissionAmount,
                'note' => "Komisi {$percent}% dari order #{$pembelian->order_id} (Profit: {$profit})",
            ]);

            Log::info("Affiliate Commission Paid: {$commissionAmount} to {$uplinkUser->username} for Order #{$pembelian->order_id}");

        } catch (\Exception $e) {
            Log::error("Failed to process affiliate commission for Order #{$pembelian->order_id}: " . $e->getMessage());
        }
    }
}
