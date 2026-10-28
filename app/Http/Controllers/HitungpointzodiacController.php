<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Berita;

class HitungpointzodiacController extends Controller
{
    public function create()
    {
        $siteName  = 'ISTANATOPUP';
        $canonical = url('/id/calculator/zodiac');

        $faq = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'Apa itu skin Zodiac di Mobile Legends?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Skin Zodiac adalah skin spesial Mobile Legends berdasarkan rasi bintang yang diperoleh lewat event gacha diamond. Kalkulator Zodiac ISTANATOPUP membantu menghitung total diamond maksimal yang dibutuhkan untuk mendapatkannya.'],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Berapa diamond yang dibutuhkan untuk mendapatkan skin Zodiac?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Total diamond maksimal dapat dihitung dengan kalkulator di atas. Masukkan data sesuai event, lalu klik Hitung untuk mengetahui estimasi diamond maksimal yang diperlukan.'],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Apakah Kalkulator Zodiac ini gratis?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Ya, Kalkulator Zodiac Mobile Legends dari ISTANATOPUP 100% gratis dan dapat digunakan kapan saja tanpa perlu login.'],
                ],
            ],
        ];

        $app = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => 'Kalkulator Zodiac Mobile Legends',
            'url' => $canonical,
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'All',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
            'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'url' => url('/id')],
        ];

        $schema = '<script type="application/ld+json">'.json_encode($faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>'
                .'<script type="application/ld+json">'.json_encode($app, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>';

        return view('template.hitungpointzodiac', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'title' => 'Kalkulator Zodiac Mobile Legends - Hitung Diamond Skin | '.$siteName,
            'meta_description' => 'Kalkulator Zodiac Mobile Legends gratis. Hitung total diamond maksimal untuk mendapatkan skin Zodiac MLBB. Cepat, mudah, dan akurat.',
            'keywords' => 'kalkulator zodiac mobile legends, zodiac mlbb, hitung diamond zodiac, skin zodiac mobile legends, kalkulator diamond mobile legends',
            'canonical_url' => $canonical,
            'schema_markup' => $schema,
        ]);
    }
    
    
}
