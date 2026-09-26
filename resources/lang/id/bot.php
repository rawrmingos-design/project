<?php

/**
 * Teks bot — Bahasa Indonesia (DEFAULT).
 *
 * Ditambah BERTAHAP per tema di Fase 1, bukan big-bang, supaya tiap commit
 * bisa diverifikasi suite-nya hijau.
 *
 * ATURAN: setiap kunci yang ditambah di sini WAJIB ditambah juga di
 * `lang/en/bot.php` — ada test parity yang mengunci kesamaan himpunan kunci.
 * Kalau tidak, terjemahan bocor jadi `bot.missing_key` di chat user.
 *
 * Kalau butuh teks WhatsApp yang SENGAJA selamanya Indonesia, biarkan literal
 * di kelasnya — jangan dipindah ke sini. Scope file ini Telegram saja.
 *
 * CATATAN TEKNIS: nilai di sini harus IDENTIK dengan yang ada di
 * BotMessageFormatter sebelum dipindah — termasuk `*tebal*` dan emoji. Fase 1
 * tidak boleh mengubah satu karakter pun (aturan fase: perilaku identik).
 *
 * Penanda `__garis bawah__` TIDAK ditulis di sini untuk judul panduan:
 * penekanan itu channel-specific (Telegram punya `__`, WhatsApp tidak) dan
 * ditambahkan `$em()` di formatter. Di sini cukup teksnya saja.
 */
return [
    // --- Sapaan (dipakai di menu & panduan) ---
    'intro_welcome' => '👋 *Selamat datang di :store*',
    'intro_tagline' => 'Penuhi kebutuhan game & aplikasi premium kamu, semua dari satu tempat.',

    // --- Menu utama ---
    'menu_title' => '🏠 *Menu Utama*',
    'menu_pick_category' => 'Pilih kategori di bawah untuk mulai. 👇',
    'menu_categories_unavailable' => 'Maaf, daftar tipe kategori sedang tidak tersedia.',
    'menu_category_fallback' => 'Kategori',

    // --- Panduan /help ---
    // Lampiran *tebal* di baris "Cek & Kelola" adalah LABEL TOMBOL yang
    // ditulis ulang di dalam teks. Label tombol belum boleh diterjemahkan
    // sampai Fase 2 (parser lintas bahasa) selesai — kalau label diterjemahkan
    // lebih dulu, parser tidak lagi mengenali tombol yang diketuk user.
    'help_title' => '📖 Panduan Singkat',
    'help_order_title' => '🛒 Cara Order',
    'help_order_step_1' => '1. Tekan *🛍️ Buka Menu*',
    'help_order_step_2' => '2. Pilih layanan, lalu pilih nominalnya',
    'help_order_step_3' => '3. Masukkan detail kontak untuk bukti pembayaran',
    'help_order_step_4' => '4. Pilih pembayaran, lalu selesaikan pembayaran',
    'help_manage_title' => '🔎 Cek & Kelola',
    'help_manage_status' => '• *📦 Cek Status* — status pesanan terakhir',
    'help_manage_history' => '• *📜 Riwayat Order* — daftar pesananmu',
    'help_manage_checkid' => '• *🔍 Cek ID Game* — pastikan nama akun benar dulu',
    'help_manage_cancel' => '• *❌ Batal Transaksi* — batalkan pesanan yang belum dibayar',
    'help_manage_deposit' => '• *💰 Deposit* — isi saldo lebih dulu',
    'help_help_title' => '❓ Butuh Bantuan?',
    'help_admin_link' => 'Ketuk tautan [💬 Klik di sini](:url), atau ketik /admin untuk membuka kontak admin. 🙏',
    'help_admin_no_link' => 'Ketik /admin untuk menghubungi admin kalau ada kendala. 🙏',

    // --- Checkout: kutipan harga & konfirmasi ---
    // Spasi pada 'Harga       Rp' SENGAJA dipertahankan (perataan kolom harga),
    // jadi jangan dirapikan editor. Placeholder :amount memakai number_format
    // ribuan titik, output identik dengan sebelum dipindah.
    'checkout_title' => '🧾 *Cek Pesanan*',
    'checkout_price' => 'Harga       Rp :amount',
    'checkout_discount' => 'Diskon      -Rp :amount',
    'checkout_admin_fee' => 'Admin       Rp :amount',
    'checkout_total' => '*Total      Rp :amount*',
    'checkout_default_payment' => 'Pembayaran',
    'checkout_send_command' => 'Kirim: `invoice :service :method <UID> [Zone_ID]`',
    'checkout_example_command' => 'Contoh: `invoice :service :method 1234567 1234`',

    // Konfirmasi berlaku 15 menit — angkanya berasal dari masa hidup cache
    // state checkout, jadi kalau TTL-nya diubah, copy ini ikut diubah.
    'checkout_confirm_expiry' => 'Konfirmasi berlaku 15 menit.',
    'checkout_btn_confirm' => '✅ Konfirmasi',
    'checkout_btn_cancel' => '❌ Batal',
    'checkout_btn_back' => '🔙 Kembali',
    'checkout_invalid_format' => 'Format ID belum sesuai.',

    // --- Checkout: baris input tujuan ---
    // 'Format: `UID`' dan 'Nickname' tidak diterjemahkan karena identik di
    // kedua bahasa — biarkan literal supaya parity guard tetap bermakna.
    'checkout_input_title' => '🎮 *Masukkan :label*',
    'checkout_input_title_email' => '📧 *Masukkan :label*',
    'checkout_input_example_uid' => 'Contoh: `12345`',
    'checkout_input_example_zone' => 'Contoh: `12345 6789`',
    'checkout_input_example_email' => 'Contoh: `nama@email.com`',
    'checkout_input_zone_options' => 'Pilihan :label:',

    // --- Cek ID Game ---
    // Bedakan "ID salah" dari "provider sedang mati": yang pertama kesalahan
    // user, yang kedua bukan — jangan sampai user mengira ID-nya salah.
    'checkid_valid_title' => '✅ *ID Valid*',
    'checkid_unavailable' => 'Validasi ID sedang tidak tersedia. Coba lagi beberapa saat.',
    'checkid_invalid' => 'ID tidak valid: :message',
    'checkid_skip' => 'Produk ini tidak memerlukan validasi ID.',
];
