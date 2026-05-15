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
            Log::debug('Affiliate commission skipped: order status is not successful', [
                'order_id' => $pembelian->order_id,
                'status' => $pembelian->status,
            ]);
            return;
        }

        // 2. Check if user exists and has uplink
        $user = $pembelian->user;
        if (!$user || !$user->uplink) {
            Log::debug('Affiliate commission skipped: downline has no uplink', [
                'order_id' => $pembelian->order_id,
                'downline_id' => $user?->id,
                'downline_username' => $user?->username,
            ]);
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
            Log::info('Affiliate commission skipped: uplink is not active affiliate', [
                'order_id' => $pembelian->order_id,
                'uplink_id' => $uplinkUser->id,
                'uplink_status' => (string) ($uplinkUser->affiliate_status ?? 'inactive'),
            ]);
            return;
        }

        // 4. Calculate Commission
        $setting = SettingWeb::query()->first();
        $percent = $setting->commission_percent ?? 20; // Default 20%
        $profit = (int) round((float) $pembelian->profit);
        
        if ($profit <= 0) {
            Log::debug('Affiliate commission skipped: order has no profit', [
                'order_id' => $pembelian->order_id,
                'profit' => $profit,
            ]);
            return; // No profit, no commission
        }

        $commissionAmount = intval($profit * ($percent / 100));

        if ($commissionAmount <= 0) {
            Log::debug('Affiliate commission skipped: calculated commission is zero', [
                'order_id' => $pembelian->order_id,
                'profit' => $profit,
                'percent' => $percent,
                'commission' => $commissionAmount,
            ]);
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
                    Log::info('Affiliate commission skipped: duplicate order commission attempt', [
                        'order_id' => $pembelian->order_id,
                        'uplink_id' => $uplinkUser->id,
                    ]);
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
                    Log::info('Affiliate commission skipped: uplink became inactive before payout', [
                        'order_id' => $pembelian->order_id,
                        'uplink_id' => $lockedUplink->id,
                        'uplink_status' => (string) ($lockedUplink->affiliate_status ?? 'inactive'),
                    ]);
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
                Log::info('Affiliate commission skipped after race: duplicate unique constraint hit', [
                    'order_id' => $pembelian->order_id,
                    'downline_id' => $user->id,
                    'uplink_id' => $uplinkUser->id,
                ]);
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
