<?php

namespace App\Support;

/**
 * Kredit editorial halaman artikel (penulis/peninjau).
 *
 * Nama brand diambil dari `APP_NAME` (config('app.name')) supaya bisa diganti
 * lewat `.env` tanpa menyentuh kode. Ini satu-satunya sumber kebenaran untuk
 * dua render path: Blade legacy (`public_theme = default`) dan React/Inertia.
 *
 * Nilai yang dipakai identik dengan Inertia `siteConfig.appName` supaya kedua
 * tema menampilkan nama yang sama.
 */
class EditorialAttribution
{
    public const FALLBACK_BRAND = 'Game Top-Up';

    public static function brand(): string
    {
        $name = trim((string) config('app.name', ''));

        return $name !== '' ? $name : self::FALLBACK_BRAND;
    }

    public static function author(): string
    {
        return 'Ditulis oleh Tim Editorial ' . self::brand();
    }

    public static function reviewer(): string
    {
        return 'Ditinjau oleh Tim Operasional ' . self::brand();
    }
}
