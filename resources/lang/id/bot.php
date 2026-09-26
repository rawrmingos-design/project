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
];
