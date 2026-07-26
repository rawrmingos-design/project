<?php

namespace App\Services\Payments;

use App\Models\Pembayaran;
use App\Support\PaymentStatus;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpirePendingPayments
{
    public function expire(int $batchSize = 100, ?int $limit = null, bool $dryRun = false): array
    {
        $batchSize = max(1, min(1000, $batchSize));
        $remaining = $limit !== null ? max(0, $limit) : null;

        $stats = [
            'scanned' => 0,
            'expired_payments' => 0,
            'expired_pembelians' => 0,
            'missing_pembelian' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        if ($remaining === 0) {
            return $stats;
        }

        $query = Pembayaran::query()
            ->select('id')
            ->whereIn('status', PaymentStatus::rawValues(PaymentStatus::PENDING))
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->orderBy('id');

        if ($remaining !== null) {
            $query->limit($remaining);
        }

        $query->chunkById($batchSize, function ($payments) use (&$stats, &$remaining, $dryRun): bool {
            foreach ($payments as $candidate) {
                if ($remaining !== null && $remaining <= 0) {
                    return false;
                }

                $stats['scanned']++;

                if ($dryRun) {
                    if ($remaining !== null) {
                        $remaining--;
                    }

                    continue;
                }

                try {
                    $result = $this->expirePayment((int) $candidate->id);

                    if ($result['missing_pembelian']) {
                        $stats['missing_pembelian']++;
                    }

                    if ($result['expired_payment']) {
                        $stats['expired_payments']++;
                    } else {
                        $stats['skipped']++;
                    }

                    if ($result['expired_pembelian']) {
                        $stats['expired_pembelians']++;
                    }
                } catch (\Throwable $exception) {
                    $stats['errors']++;

                    Log::warning('Expire pending payment failed', [
                        'pembayaran_id' => $candidate->id,
                        'error' => $exception->getMessage(),
                    ]);
                }

                if ($remaining !== null) {
                    $remaining--;
                }
            }

            return $remaining === null || $remaining > 0;
        });

        return $stats;
    }

    private function expirePayment(int $paymentId): array
    {
        return DB::transaction(function () use ($paymentId): array {
            $payment = Pembayaran::query()
                ->whereKey($paymentId)
                ->lockForUpdate()
                ->first();

            if (! $payment || ! $payment->isExpiredUnpaid()) {
                return [
                    'expired_payment' => false,
                    'expired_pembelian' => false,
                    'missing_pembelian' => false,
                ];
            }

            $pembelian = $payment->pembelian()->lockForUpdate()->first();
            $previousPembelianStatus = $pembelian?->status;
            $missingPembelian = $pembelian === null;

            $expiredPayment = $payment->syncExpiredStatus();
            $pembelian?->refresh();

            $expiredPembelian = $pembelian !== null
                && ! $pembelian->hasStatus($previousPembelianStatus ?? '')
                && $pembelian->hasStatus(PembelianStatus::EXPIRED);

            return [
                'expired_payment' => $expiredPayment,
                'expired_pembelian' => $expiredPembelian,
                'missing_pembelian' => $missingPembelian,
            ];
        });
    }
}
