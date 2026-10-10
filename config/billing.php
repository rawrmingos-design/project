<?php

/*
|--------------------------------------------------------------------------
| Billing Berulang (Langganan Tenant)
|--------------------------------------------------------------------------
|
| PENTING: semua nilai dibaca lewat config(), BUKAN env() langsung. Saat
| bootstrap/cache/config.php ada, Laravel melewati pembacaan .env dan env()
| di luar file config mengembalikan null.
|
| Catatan: `docker restart` TIDAK membangun ulang config cache (artisan
| optimize hanya jalan saat deploy). Setelah mengubah .env, jalankan
| `php artisan config:clear && php artisan config:cache`.
|
*/

return [

    /*
    | Saklar utama penagihan otomatis. Dimatikan = tidak ada invoice
    | perpanjangan, tidak ada suspend otomatis. Bisa diubah tanpa deploy.
    */
    'renewal_enabled' => env('BILLING_RENEWAL_ENABLED', false),

    /*
    | Berapa hari SEBELUM current_period_end invoice perpanjangan diterbitkan.
    | Bayar di jendela ini = tanpa denda (periode masih aktif).
    */
    'reminder_days_before' => (int) env('BILLING_REMINDER_DAYS_BEFORE', 3),

    /*
    | Masa tenggang setelah current_period_end lewat, sebelum tenant
    | disuspend. Selama masa ini tagihan sudah kena denda.
    */
    'grace_days' => (int) env('BILLING_GRACE_DAYS', 3),

    /*
    | Denda keterlambatan: satu angka FLAT untuk seluruh masa tenggang,
    | tidak bertambah per hari. Ubah di sini saja — jangan disebar sebagai
    | literal di beberapa file.
    */
    'late_fee' => (int) env('BILLING_LATE_FEE', 10000),

    /*
    | Umur tautan pembayaran Duitku (menit). Duitku membatasi expiryPeriod.
    | Jendela H-3 + tenggang 3 hari lebih panjang dari ini, jadi invoice
    | diterbitkan ulang harian sampai dibayar atau tenant disuspend.
    */
    'payment_link_lifetime_minutes' => (int) env('BILLING_PAYMENT_LINK_MINUTES', 1440),

];
