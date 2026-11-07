<?php

namespace App\Support;

/**
 * Pemformatan rupiah untuk tampilan.
 *
 * Dipakai di Blade supaya angka dari database tidak perlu dirapikan di
 * controller: satu tempat untuk aturan pemisah ribuan, dan harga nol tidak
 * pernah tampil sebagai "Rp 0" yang membingungkan di halaman jualan
 * (harga nol biasanya berarti data belum diisi).
 */
class Rupiah
{
    /**
     * Format penuh: 330000 -> "330.000".
     */
    public static function full(int|float|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 0, ',', '.');
    }

    /**
     * Dengan awalan "Rp".
     */
    public static function rp(int|float|null $amount): string
    {
        return 'Rp ' . self::full($amount);
    }

    /**
     * Untuk halaman jualan: kosong kalau belum ada harga.
     *
     * Menampilkan "Rp 0" pada paket berbayar membuat calon pembeli merasa
     * harganya gratis, atau menganggap situsnya rusak. Lebih baik tanda pisah.
     */
    public static function short(int|float|null $amount): string
    {
        $value = (int) ($amount ?? 0);

        return $value > 0 ? self::full($value) : '—';
    }
}
