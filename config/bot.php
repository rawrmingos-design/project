<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rekonsiliasi checkout bot yang menggantung
    |--------------------------------------------------------------------------
    |
    | Jalur checkout bot menandai `provider_dispatched_at` SEBELUM memanggil
    | payment gateway, dan `DuitkuInvoiceService::createForPembelian()`
    | menangkap `Throwable` (termasuk timeout) lalu mengembalikan
    | `success = false`. Akibatnya `success = false` TIDAK bisa dibedakan
    | antara "provider menolak" dan "provider tidak pernah menerima", dan
    | intent berakhir di `requires_reconciliation`.
    |
    | Status itu sebelumnya tidak punya pembaca sama sekali: user dibalas
    | "sedang direkonsiliasi, jangan membuat transaksi ulang" untuk selamanya.
    | `bot:reconcile-checkout` menutup lubang itu — setelah jendela tunggu
    | lewat, intent yang TIDAK terbukti punya order dilepas menjadi
    | `failed_retryable` sehingga user bisa memulai checkout ulang.
    |
    | NONAKTIF secara default: jalur ini menyentuh uang, jangan pernah
    | diaktifkan tanpa sengaja.
    |
    */

    'reconcile_checkout_enabled' => (bool) env('BOT_RECONCILE_CHECKOUT_ENABLED', false),

    /*
    | Jendela tunggu sebelum intent dianggap boleh direkonsiliasi. Selama
    | jendela ini kita BELUM boleh mengklaim provider tidak pernah menerima
    | request — tagihan bisa saja masih dalam perjalanan.
    */
    'reconcile_checkout_after_minutes' => (int) env('BOT_RECONCILE_CHECKOUT_AFTER_MINUTES', 10),

];
