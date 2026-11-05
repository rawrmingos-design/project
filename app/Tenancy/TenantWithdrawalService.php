<?php

namespace App\Tenancy;

use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

/**
 * Keputusan Admin atas satu pengajuan pencairan.
 *
 * Saldo dipotong saat pengajuan (WithdrawalController::store), jadi di sini
 * hanya dua hal yang boleh terjadi:
 *
 *   approve() -> status success, simpan bukti transfer. Saldo TIDAK disentuh.
 *   reject()  -> status rejected, kembalikan saldo SEKALI.
 *
 * Keduanya mengunci baris dan menolak bila status bukan `pending`. Tanpa itu,
 * klik ganda (atau dua admin bersamaan) pada "Tolak" akan mengembalikan saldo
 * dua kali — uang yang lahir dari klik.
 */
class TenantWithdrawalService
{
    public function approve(Withdrawal $withdrawal, ?string $proofPath): bool
    {
        return DB::transaction(function () use ($withdrawal, $proofPath): bool {
            $locked = Withdrawal::query()->whereKey($withdrawal->getKey())->lockForUpdate()->first();

            if (! $locked || ! $this->isPending($locked)) {
                return false;
            }

            $locked->status = 'success';
            $locked->bukti_transfer = $proofPath;
            $locked->save();

            return true;
        });
    }

    public function reject(Withdrawal $withdrawal): bool
    {
        return DB::transaction(function () use ($withdrawal): bool {
            $locked = Withdrawal::query()->whereKey($withdrawal->getKey())->lockForUpdate()->first();

            if (! $locked || ! $this->isPending($locked)) {
                return false;
            }

            $locked->status = 'rejected';
            $locked->save();

            if (blank($locked->user_id)) {
                return true;
            }

            // Kembalikan uang yang tadi dipotong saat pengajuan.
            $owner = User::withoutGlobalScopes()
                ->whereKey($locked->user_id)
                ->lockForUpdate()
                ->first();

            if ($owner) {
                $owner->balance = (int) round((float) $owner->balance) + (int) round((float) $locked->total_transfer);
                $owner->save();
            }

            return true;
        });
    }

    private function isPending(Withdrawal $withdrawal): bool
    {
        return strtolower(trim((string) $withdrawal->status)) === 'pending';
    }
}
