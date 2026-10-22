<?php

namespace App\Services\Voucher;

use App\Models\Tenant;
use App\Models\Voucher;

/**
 * Satu-satunya tempat kode voucher diterjemahkan menjadi baris `vouchers`.
 *
 * Kenapa dipusatkan: sebelum ini ada sebelas tempat yang memanggil
 * `Voucher::where('kode', ...)` tanpa scope — OrderController (tujuh blok),
 * Api\OrderController (tiga), Api\GatewayController, CheckoutOrderService,
 * GatewayPricingService, dan VoucherService. Satu titik yang terlewat saat
 * menambahkan `tenant_id` berarti voucher tenant A tetap berlaku di toko
 * tenant B. Karena itu semua pemanggil harus lewat sini.
 *
 * ISOLASI KETAT: kupon hanya berlaku di tempat ia dibuat.
 *   - situs platform  -> hanya voucher `tenant_id NULL`
 *   - toko tenant     -> hanya voucher milik tenant itu
 *
 * Tidak ada lagi "voucher platform berlaku di semua toko". Alasan aturan ini
 * dipilih ada di `Voucher::scopeAvailableTo()`.
 */
class VoucherResolver
{
    /**
     * @param  Tenant|int|null  $tenant  null = situs platform (tanpa tenant)
     */
    public function resolve(Tenant|int|null $tenant, ?string $code): ?Voucher
    {
        return $this->query($tenant, $code, false);
    }

    /**
     * Sama seperti resolve(), tapi dengan row lock untuk pemakaian stok.
     *
     * Dipakai di dalam transaksi checkout: tanpa lock, dua pembeli bersamaan
     * bisa memakai stok terakhir.
     */
    public function resolveForUpdate(Tenant|int|null $tenant, ?string $code): ?Voucher
    {
        return $this->query($tenant, $code, true);
    }

    private function query(Tenant|int|null $tenant, ?string $code, bool $lock): ?Voucher
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        $query = Voucher::query()
            ->availableTo($tenant instanceof Tenant ? $tenant->id : $tenant)
            ->where('kode', $code);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
