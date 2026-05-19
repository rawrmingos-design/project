<?php

namespace App\Helpers;

use Mews\Purifier\Facades\Purifier;

/**
 * HtmlSanitizer — FIX #3 XSS Stored
 *
 * Wrapper di atas mews/purifier (HTMLPurifier library).
 * Jauh lebih aman dari strip_tags() karena memahami struktur HTML
 * dan memblokir semua teknik XSS yang dikenal.
 */
class HtmlSanitizer
{
    private static function resolveProfile(string $name): ?array
    {
        $profile = config("purifier.settings.{$name}");

        return is_array($profile) ? $profile : null;
    }

    /**
     * Sanitasi HTML rich-text (untuk deskripsi game, artikel, deskripsi_field).
     * Menggunakan profile 'default' dari config/purifier.php.
     */
    public static function clean(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $defaultProfile = self::resolveProfile('default');

        return $defaultProfile
            ? Purifier::clean($html, $defaultProfile)
            : Purifier::clean($html);
    }

    /**
     * Sanitasi konten artikel/legal dengan profile yang aman.
     * Catatan: `custom_definition` di config bukan profile settings, jadi
     * kita tetap gunakan profile settings (`article` jika ada, fallback `default`)
     * agar kompatibel dengan versi purifier terbaru.
     */
    public static function cleanArticle(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        $articleProfile = self::resolveProfile('article') ?? self::resolveProfile('default');

        return $articleProfile
            ? Purifier::clean($html, $articleProfile)
            : Purifier::clean($html);
    }

    /**
     * Sanitasi minimal — hanya teks biasa, strip semua HTML.
     * Untuk field yang seharusnya tidak mengandung HTML sama sekali.
     */
    public static function cleanText(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        return strip_tags((string) $text);
    }
}
