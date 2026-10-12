<?php

namespace App\Tenancy;

use App\Models\SubscriptionInvoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * D4: tautan bayar untuk halaman tagihan.
 *
 * Aturan pentingnya: tautan gateway Duitku hidup 1440 menit, sedangkan tautan
 * kita (email/WA) tidak boleh pernah mati. Karena itu tautan yang sudah terkirim
 * diarahkan ke halaman kita, dan halaman ini yang menyediakan tautan bayar —
 * diregenerasi di belakang layar kalau yang lama sudah kedaluwarsa.
 *
 * Tautan yang sudah terkirim TIDAK pernah berubah: yang diregenerasi hanya
 * `metadata.duitku.*` pada invoice yang sama, dan `gateway_ref` dibiarkan.
 */
class SubscriptionPaymentLinkResolver
{
    /**
     * Tautan bayar yang aman ditampilkan ke pengunjung, atau null kalau tidak
     * ada yang perlu dibayar.
     */
    public function resolve(SubscriptionInvoice $invoice): ?string
    {
        $url = trim((string) data_get($invoice->metadata, 'duitku.payment_url', ''));

        // Tautan masih hidup → JANGAN panggil gateway. Halaman tagihan bisa
        // dibuka berkali-kali (email, WA, refresh); memanggil provider tiap kali
        // berarti menagih kuota dan memperpendek umur tautan tanpa alasan.
        if ($url !== '' && $this->linkIsAlive($invoice)) {
            return $invoice->status === SubscriptionInvoice::STATUS_PENDING ? $url : null;
        }

        // Sudah lunas / dibatalkan / kedaluwarsa / nominal nol → tidak ada yang
        // perlu dibayar, jadi tidak ada tautan yang perlu dibuat.
        if ($invoice->status !== SubscriptionInvoice::STATUS_PENDING || (int) $invoice->amount <= 0) {
            return null;
        }

        try {
            // Kunci singkat: owner yang menunggu pembayaran akan me-refresh halaman
            // berkali-kali, dan tiap request tanpa kunci akan memanggil Duitku
            // dengan `merchantOrderId` yang sama. Satu tautan dibuat per putaran.
            $lock = Cache::lock('billing-link:' . $invoice->getKey(), 15);

            if (! $lock->get()) {
                return null;
            }

            try {
                // D4: jalur yang sudah terbukti (dipakai pendaftaran, perpanjangan,
                // dan aksi "retry" di admin), bukan integrasi baru. `gateway_ref`
                // maupun `public_token` TIDAK diubah — webhook Duitku mencari
                // invoice lewat `gateway_ref`, jadi merotasinya akan membuat
                // pembayaran dari tautan lama hilang tanpa jejak.
                $invoice = app(DuitkuSubscriptionPaymentService::class)->createAndStoreInvoice($invoice);
            } finally {
                $lock->release();
            }
        } catch (\Throwable $e) {
            // Jangan pernah melempar error gateway ke pengunjung: halaman tagihan
            // tetap harus tampil, hanya tombolnya yang absen.
            Log::warning('Gagal regenerasi tautan bayar tagihan tenant.', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return (string) data_get($invoice->metadata, 'duitku.payment_url', '') ?: null;
    }

    /**
     * Tautan gateway hanya hidup selama `expiryPeriod`. Kalau `expires_at` tidak
     * tercatat, jatuh tempo invoice dipakai sebagai batas aman — jangan pernah
     * menganggap tautan hidup tanpa dasar.
     */
    protected function linkIsAlive(SubscriptionInvoice $invoice): bool
    {
        $expires = data_get($invoice->metadata, 'duitku.expires_at')
            ?: $invoice->due_date;

        if (blank($expires)) {
            return false;
        }

        try {
            return Carbon::parse($expires)->isFuture();
        } catch (\Throwable) {
            return false;
        }
    }
}
