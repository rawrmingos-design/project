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
    /**
     * Sanitasi HTML rich-text (untuk deskripsi game, artikel, deskripsi_field).
     * Menggunakan profile 'default' dari config/purifier.php.
     */
    public static function clean(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        return Purifier::clean($html, 'default');
    }

    /**
     * Sanitasi lebih ketat untuk konten artikel — izinkan lebih banyak tag HTML5.
     * Cocok untuk konten artikel blog yang sering menggunakan heading, tabel, blockquote.
     */
    public static function cleanArticle(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        return Purifier::clean($html, 'custom_definition');
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
