<?php

namespace App\Services;

use App\Models\Pembelian;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Restore stok voucher (kode promo) untuk order yang berakhir gagal.
 *
 * Parity dengan PointService::refundRedeemedPoints(): setiap order yang
 * dibatalkan/di-expire/di-refund setelah stok voucher terpotong saat checkout
 * harus mengembalikan stoknya — TAPI maksimal sekali per order.
 *
 * Idempotency: lock baris pembelian + stempel `voucher_stock_restored_at`
 * (pola sama seperti OrderRefundService untuk refund saldo), sehingga aman
 * dipanggil dari callback gateway yang bisa terkirim ulang, command expiry
 * yang jalan tiap menit, maupun aksi admin.
 *
 * Panggil HANYA dari konteks terminal (Gagal/Expired/Cancelled/Refunded).
 */
class VoucherService
{
    public function restoreStockForOrder(Pembelian $pembelian): bool
    {
        if (! $this->isPromoCodeOrder($pembelian)) {
            return false;
        }

        if ($pembelian->voucher_stock_restored_at !== null) {
            return false;
        }

        $restored = false;

        DB::transaction(function () use ($pembelian, &$restored): void {
            // Re-fetch dengan row lock + re-check stempel agar dua callback
            // paralel tidak sama-sama mengembalikan stok.
            $locked = Pembelian::query()
                ->whereKey($pembelian->getKey())
                ->whereNull('voucher_stock_restored_at')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return;
            }

            $voucherCode = trim((string) ($locked->voucher ?? ''));

            if ($voucherCode === '') {
                return;
            }

            $affected = Voucher::query()
                ->where('kode', $voucherCode)
                ->increment('stock');

            $locked->forceFill(['voucher_stock_restored_at' => now()])->saveQuietly();

            if ($affected > 0) {
                $restored = true;

                Log::info('Voucher stock restored for terminal order', [
                    'order_id' => $locked->order_id,
                ]);
            } else {
                Log::warning('Voucher stock restore skipped: voucher row not found', [
                    'order_id' => $locked->order_id,
                ]);
            }
        });

        return $restored;
    }

    /**
     * Kolom `voucher` pada pembelians juga menyimpan SN untuk produk bertipe
     * voucher — baris seperti itu bukan pemakaian kode promo.
     */
    private function isPromoCodeOrder(Pembelian $pembelian): bool
    {
        $voucherCode = trim((string) ($pembelian->voucher ?? ''));

        if ($voucherCode === '') {
            return false;
        }

        return strtolower(trim((string) $pembelian->tipe_transaksi)) !== 'voucher';
    }
}
