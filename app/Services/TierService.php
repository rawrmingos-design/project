<?php

namespace App\Services;

use App\Models\User;
use App\Models\Pembelian;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\Log;

class TierService
{
    /**
     * Check and upgrade user tier based on successful transaction count.
     *
     * @param User $user
     * @return void
     */
    public function checkAndUpgradeTier(User $user): void
    {
        // Only check for Member, Gold, Platinum roles
        // Skip Admin or other special roles if necessary
        if ($user->role === 'Admin') {
            return;
        }

        // Count successful transactions
        // Assuming 'username' in Pembelian matches User 'username' or relationship exists
        // Based on User model, relationship is hasMany Pembelian via username
        $transactionCount = Pembelian::where('username', $user->username)
            ->whereIn('status', PembelianStatus::aliasesFor(PembelianStatus::SUCCESS))
            ->count();
            
        $currentRole = $user->role;
        $newRole = $currentRole;

        // Fetch configuration from database
        $settings = \App\Models\SettingWeb::first();
        $goldThreshold = $settings->trx_count_gold ?? 50;
        $platinumThreshold = $settings->trx_count_platinum ?? 100;

        // Logic:
        // Member: < Gold Threshold
        // Gold: >= Gold Threshold
        // Platinum: >= Platinum Threshold
        
        if ($transactionCount >= $platinumThreshold) {
            $newRole = 'Platinum';
        } elseif ($transactionCount >= $goldThreshold) {
            $newRole = 'Gold';
        } else {
            // Optional: Downgrade logic? Usually we only upgrade.
            // Let's stick to upgrade only to prevent frustration.
            // But if they are Member and have 0, they stay Member.
             if ($currentRole !== 'Platinum' && $currentRole !== 'Gold') {
                $newRole = 'Member';
            }
        }

        // Only update if role changes and it's an upgrade (or handling Downgrades if required)
        // For now, let's allow moving up.
        // We also need to avoid overwriting if they are already higher (e.g. manually set to Platinum but count is low)
        // But the requirement says "Based on Count", so we should strictly follow count OR allow manual override?
        // Let's assume strict count for now, but prevent downgrading from manual set might be safer.
        // However, "Automatic Role Switching" implies the system rules.
        
        // Strict Hierarchy: Member < Gold < Platinum
        $tiers = ['Member' => 1, 'Gold' => 2, 'Platinum' => 3];
        
        $currentLevel = $tiers[$currentRole] ?? 0;
        $newLevel = $tiers[$newRole] ?? 0;

        if ($newLevel > $currentLevel) {
            $user->update(['role' => $newRole]);
            Log::info("User {$user->username} upgraded to {$newRole} (Count: {$transactionCount})");
            
            // TODO: Send notification about upgrade?
        }
    }
}
