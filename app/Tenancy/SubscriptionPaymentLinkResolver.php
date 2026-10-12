<?php

namespace App\Tenancy;

use App\Models\SubscriptionInvoice;
use Carbon\Carbon;

/**
 * D4: tautan bayar untuk halaman tagihan.
 *
 * Aturan pentingnya: tautan gateway Duitku hidup 1440 menit, sedangkan tautan
 * kita (email/WA) tidak boleh pernah mati. Karena itu tautan yang sudah terkirim
 * diarahkan ke halaman kita, dan halaman ini yang menyediakan tautan bayar —
 * diregenerasi di belakang layar kalau yang lama sudah kedaluwarsa.
 *
 * Task 3 hanya membaca tautan yang masih hidup. Regenerasi ditambahkan di Task 4.
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

        if ($url === '' || ! $this->linkIsAlive($invoice)) {
            return null;
        }

        // Sudah lunas / dibatalkan / kedaluwarsa → tidak ada yang perlu dibayar.
        if ($invoice->status !== SubscriptionInvoice::STATUS_PENDING) {
            return null;
        }

        if ((int) $invoice->amount <= 0) {
            return null;
        }

        return $url;
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
