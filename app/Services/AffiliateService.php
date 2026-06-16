<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\User;
use App\Models\SettingWeb;
use App\Models\AffiliateHistory;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AffiliateService
{
    public function processCommission(Pembelian $pembelian): void
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
        $uplinkUser = User::query()->where('username', $user->uplink)->first();
        if (!$uplinkUser) {
            Log::warning('Affiliate commission skipped: uplink user not found', [
                'order_id' => $pembelian->order_id,
                'downline_id' => $user->id,
                'uplink_username' => $user->uplink,
            ]);
            return;
        }

        if ((int) $uplinkUser->id === (int) $user->id) {
            Log::warning('Affiliate commission skipped: self referral detected', [
                'order_id' => $pembelian->order_id,
                'user_id' => $user->id,
                'username' => $user->username,
            ]);
            return;
        }

        if (! $this->isAffiliateActive($uplinkUser)) {
            return;
        }

        // 4. Calculate Commission
        $setting = SettingWeb::query()->first();
        $percent = $setting->commission_percent ?? 20; // Default 20%
        $profit = (int) round((float) $pembelian->profit);
        
        if ($profit <= 0) {
            return; // No profit, no commission
        }

        $commissionAmount = intval($profit * ($percent / 100));

        if ($commissionAmount <= 0) {
            return;
        }

        // 5. Distribute Commission (atomic + idempotent)
        try {
            DB::transaction(function () use ($pembelian, $user, $uplinkUser, $commissionAmount, $percent, $profit): void {
                $duplicate = AffiliateHistory::query()
                    ->where('order_id', $pembelian->order_id)
                    ->lockForUpdate()
                    ->exists();
                if ($duplicate) {
                    return;
                }

                $lockedUplink = User::query()
                    ->whereKey($uplinkUser->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedUplink) {
                    Log::warning('Affiliate commission skipped: uplink not found during lock', [
                        'order_id' => $pembelian->order_id,
                        'uplink_id' => $uplinkUser->id,
                    ]);
                    return;
                }

                if ((int) $lockedUplink->id === (int) $user->id) {
                    Log::warning('Affiliate commission skipped: self referral detected during lock', [
                        'order_id' => $pembelian->order_id,
                        'user_id' => $user->id,
                    ]);
                    return;
                }

                if (! $this->isAffiliateActive($lockedUplink)) {
                    return;
                }

                $lockedUplink->increment('balance', $commissionAmount);

                AffiliateHistory::query()->create([
                    'uplink_id' => $lockedUplink->id,
                    'downlink_id' => $user->id,
                    'order_id' => $pembelian->order_id,
                    'amount' => $commissionAmount,
                    'note' => "Komisi {$percent}% dari order #{$pembelian->order_id} (Profit: {$profit})",
                ]);

                Log::info('Affiliate commission paid', [
                    'order_id' => $pembelian->order_id,
                    'downline_id' => $user->id,
                    'uplink_id' => $lockedUplink->id,
                    'commission' => $commissionAmount,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            if ($this->isDuplicateCommissionConstraintViolation($exception)) {
                return;
            }

            Log::error("Failed to process affiliate commission for Order #{$pembelian->order_id}: " . $exception->getMessage());
        } catch (\Exception $e) {
            Log::error("Failed to process affiliate commission for Order #{$pembelian->order_id}: " . $e->getMessage());
        }
    }

    private function isAffiliateActive(User $user): bool
    {
        if (method_exists($user, 'isAffiliateActive')) {
            return (bool) $user->isAffiliateActive();
        }

        return strtolower(trim((string) ($user->affiliate_status ?? ''))) === 'active';
    }

    private function isDuplicateCommissionConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $message = strtolower($exception->getMessage());

        if ($sqlState === '23000' || str_contains($message, 'unique constraint')) {
            return str_contains($message, 'affiliate_histories_order_id_unique')
                || (str_contains($message, 'affiliate_histories') && str_contains($message, 'order_id'));
        }

        return false;
    }
}
