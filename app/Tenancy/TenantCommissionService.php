<?php

namespace App\Tenancy;

use App\Models\Pembelian;
use App\Support\PembelianStatus;
use Illuminate\Support\Facades\DB;

class TenantCommissionService
{
    public function creditIfEligible(Pembelian $pembelian): bool
    {
        if (blank($pembelian->tenant_id) || (int) $pembelian->profit <= 0) {
            return false;
        }

        if (PembelianStatus::normalize($pembelian->status) !== PembelianStatus::SUCCESS) {
            return false;
        }

        return DB::transaction(function () use ($pembelian): bool {
            $lockedOrder = Pembelian::withoutGlobalScope('tenant')
                ->whereKey($pembelian->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedOrder || $lockedOrder->tenant_commission_credited_at !== null) {
                return false;
            }

            $tenant = $lockedOrder->tenant;
            $owner = $tenant?->owner;

            if (! $owner) {
                return false;
            }

            $owner->increment('balance', (int) $lockedOrder->profit);

            $lockedOrder->forceFill([
                'tenant_commission_credited_at' => now(),
            ])->saveQuietly();

            return true;
        });
    }
}
