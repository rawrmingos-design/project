<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\User;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Handles automatic saldo refund for failed H2H orders.
 *
 * Eligibility rules:
 *  - Order must be H2H (traffic_source = 'reseller_h2h')
 *  - Order status must be FAILED or CANCELLED
 *  - Order must not have been refunded before (refunded_at IS NULL)
 *
 * Idempotency: uses SELECT ... FOR UPDATE + refunded_at timestamp to
 * ensure exactly-once refund even if the webhook fires multiple times.
 */
class OrderRefundService
{
    /**
     * Process refund if the order is eligible.
     *
     * @return bool true if a refund was issued, false if skipped (already refunded or not eligible)
     */
    public function refundIfEligible(Pembelian $pembelian): bool
    {
        // Guard 1: Only H2H orders get auto-refund.
        // Web orders use a separate CS-assisted manual refund process.
        if ($pembelian->traffic_source !== 'reseller_h2h') {
            return false;
        }

        // Guard 2: Only refund when status is definitively failed or cancelled.
        $normalizedStatus = PembelianStatus::normalize($pembelian->status);
        if (! in_array($normalizedStatus, [PembelianStatus::FAILED, PembelianStatus::CANCELLED], true)) {
            return false;
        }

        // Guard 3: Skip if already refunded (idempotency pre-check, no lock needed yet).
        if ($pembelian->refunded_at !== null) {
            return false;
        }

        $refundAmount = (int) $pembelian->harga;

        if ($refundAmount <= 0) {
            Log::warning('OrderRefundService: refund skipped — zero or negative amount', [
                'pembelian_id' => $pembelian->getKey(),
                'order_id'     => $pembelian->order_id,
                'harga'        => $pembelian->harga,
            ]);

            return false;
        }

        $refunded = false;

        DB::transaction(function () use ($pembelian, $refundAmount, &$refunded) {
            // Re-fetch with row lock to prevent race condition when webhook fires twice
            // in rapid succession (e.g. provider retry).
            $locked = Pembelian::where('id', $pembelian->getKey())
                ->whereNull('refunded_at')   // ONLY lock if not yet refunded
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                // Another process already processed the refund — skip.
                return;
            }

            // Resolve the user via the username relation (Pembelian::user() is keyed on username)
            $user = User::where('username', $locked->username)->first();

            if (! $user) {
                Log::error('OrderRefundService: user not found for refund', [
                    'pembelian_id' => $locked->getKey(),
                    'order_id'     => $locked->order_id,
                    'username'     => $locked->username,
                ]);

                return;
            }

            // Atomically credit saldo back to user
            User::where('id', $user->getKey())->increment('balance', $refundAmount);

            // Mark as refunded so idempotency check catches future duplicate webhook calls
            $locked->update([
                'refunded_at'   => now(),
                'refund_amount' => $refundAmount,
            ]);

            Log::info('OrderRefundService: H2H order refunded', [
                'pembelian_id'  => $locked->getKey(),
                'order_id'      => $locked->order_id,
                'username'      => $locked->username,
                'refund_amount' => $refundAmount,
                'status'        => $locked->status,
            ]);

            $refunded = true;
        });

        // TODO Phase 4: send reseller callback notification about refund
        return $refunded;
    }
}
