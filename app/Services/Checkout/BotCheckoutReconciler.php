<?php

namespace App\Services\Checkout;

use App\Models\BotCheckoutIntent;
use App\Models\Pembelian;

/**
 * Menjawab satu pertanyaan untuk intent yang menggantung di
 * `requires_reconciliation`: "apakah provider BENAR-BENAR menghasilkan
 * tagihan/order untuk intent ini?"
 *
 * Sengaja dipisah dari `BotCheckoutIntentService` supaya keputusan status
 * (mesin state, bisa diuji tanpa jaringan) terpisah dari pembacaan provider
 * (butuh HTTP, bisa gagal/timeout).
 *
 * Batas kejujuran kelas ini: ia hanya bisa MEMBUKTIKAN ADA order. Kalau tidak
 * ada bukti, hasilnya `null` — dan pemanggil melepas intent. Itu aman karena
 * intent yang gagal sebelum order tersimpan memang tidak meninggalkan tagihan
 * lokal yang bisa dibayar; alasannya didokumentasikan di kolom `failure_code`.
 */
class BotCheckoutReconciler
{
    /**
     * @return string|null order_id kalau order-nya benar-benar ada, null kalau tidak.
     */
    public function resolveOrderForIntent(BotCheckoutIntent $intent): ?string
    {
        $reference = trim((string) $intent->merchant_reference);

        if ($reference === '') {
            return null;
        }

        // 1. Order lokal. Jalur normal menyimpan `pembelians.order_id` =
        //    `merchant_reference` (lihat `CheckoutOrderService`), jadi ini
        //    bukti langsung bahwa order-nya sempat terbentuk.
        $order = Pembelian::query()
            ->where('order_id', $reference)
            ->first();

        if ($order) {
            return (string) $order->order_id;
        }

        // 2. Tidak ada order lokal. Tidak ada lagi bukti yang bisa dikumpulkan
        //    tanpa menanyakan status provider satu per satu (butuh kredensial
        //    dan referensi internal masing-masing gateway, mis. `DUITKU-{id}`
        //    atau `reference` Tripay yang tidak disimpan di intent).
        //
        //    Keputusan produk: jangan menahan user lebih lama hanya karena kita
        //    tidak sanggup membuktikan. Tanpa baris `pembelians`/`pembayaran`,
        //    tidak ada tagihan lokal yang bisa dibayar, jadi melepas intent
        //    tidak berisiko menggandakan penagihan.
        return null;
    }
}
