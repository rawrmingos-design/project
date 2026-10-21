<?php

namespace App\Services\Voucher;

use App\Models\Tenant;
use App\Models\Voucher;

/**
 * Satu-satunya tempat kode voucher diterjemahkan menjadi baris `vouchers`.
 *
 * Kenapa dipusatkan: sebelum ini ada lima tempat yang memanggil
 * `Voucher::where('kode', ...)` tanpa scope — OrderController (dua blok),
 * Api\OrderController (dua tempat), CheckoutOrderService, VoucherService, dan
 * VoucherController. Satu titik yang terlewat saat menambahkan `tenant_id`
 * berarti voucher tenant A tetap berlaku di toko tenant B. Karena itu semua
 * pemanggil harus lewat sini.
 *
 * Urutan pencarian di situs tenant:
 *   1. voucher milik tenant itu
 *   2. voucher platform (tenant_id NULL)
 *
 * Voucher tenant didahulukan supaya tenant bisa memakai kode umum seperti
 * "PROMO10" tanpa bentrok dengan promo platform berkode sama.
 */
class VoucherResolver
{
    /**
     * @param  Tenant|int|null  $tenant  null = situs platform (tanpa tenant)
     */
    public function resolve(Tenant|int|null $tenant, ?string $code): ?Voucher
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        if ($tenantId === null) {
            // Situs platform tidak boleh menemukan voucher milik tenant mana pun.
            return Voucher::query()->platform()->where('kode', $code)->first();
        }

        return Voucher::query()
            ->availableTo((int) $tenantId)
            ->where('kode', $code)
            // Voucher milik tenant menang atas voucher platform berkode sama.
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Sama seperti resolve(), tapi dengan row lock untuk pemakaian stok.
     *
     * Dipakai di dalam transaksi checkout: tanpa lock, dua pembeli bersamaan
     * bisa memakai stok terakhir.
     */
    public function resolveForUpdate(Tenant|int|null $tenant, ?string $code): ?Voucher
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        if ($tenantId === null) {
            return Voucher::query()->platform()->where('kode', $code)->lockForUpdate()->first();
        }

        return Voucher::query()
            ->availableTo((int) $tenantId)
            ->where('kode', $code)
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }
}
