<?php

/**
 * Teks bot — Bahasa Indonesia (DEFAULT).
 *
 * Isi awal sengaja MINIMAL (hanya beberapa kunci jalur sapaan & bantuan).
 * Penambahan kunci dilakukan bertahap per tema di Fase 1, bukan big-bang,
 * supaya tiap commit bisa diverifikasi suite-nya hijau.
 *
 * ATURAN: setiap kunci yang ditambah di sini WAJIB ditambah juga di
 * `lang/en/bot.php` — ada test parity yang mengunci kesamaan himpunan kunci.
 * Kalau tidak, terjemahan bocor jadi `bot.missing_key` di chat user.
 *
 * Kalau butuh teks WhatsApp yang SENGAJA selamanya Indonesia, biarkan literal
 * di kelasnya — jangan dipindah ke sini. Scope file ini Telegram saja.
 */
return [
    'intro_welcome' => '👋 *Selamat datang di :store*',
    'intro_tagline' => 'Penuhi kebutuhan game & aplikasi premium kamu, semua dari satu tempat.',
];
