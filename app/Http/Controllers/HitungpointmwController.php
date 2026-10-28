<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Berita;
class HitungpointmwController extends Controller
{
    public function create()
    {
        $siteName  = 'ISTANATOPUP';
        $canonical = url('/id/calculator/magic-wheel');

        $faq = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'Apa itu Magic Wheel di Mobile Legends?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Magic Wheel adalah event gacha Mobile Legends untuk mendapatkan skin Legend. Kalkulator Magic Wheel ISTANATOPUP membantu menghitung total diamond maksimal yang dibutuhkan untuk menyelesaikan event dan mendapatkan skin tersebut.'],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Berapa diamond yang dibutuhkan untuk menyelesaikan Magic Wheel?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Jumlah maksimal diamond bisa dihitung dengan kalkulator di atas. Masukkan data sesuai event, lalu klik Hitung untuk mengetahui estimasi diamond maksimal yang diperlukan.'],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Apakah Kalkulator Magic Wheel ini gratis?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Ya, Kalkulator Magic Wheel Mobile Legends dari ISTANATOPUP 100% gratis dan dapat digunakan kapan saja tanpa perlu login.'],
                ],
            ],
        ];

        $app = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => 'Kalkulator Magic Wheel Mobile Legends',
            'url' => $canonical,
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'All',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
            'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'url' => url('/id')],
        ];

        $schema = '<script type="application/ld+json">'.json_encode($faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>'
                .'<script type="application/ld+json">'.json_encode($app, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>';

        return view('template.hitungpointmw', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'title' => 'Kalkulator Magic Wheel Mobile Legends - Hitung Diamond | '.$siteName,
            'meta_description' => 'Kalkulator Magic Wheel Mobile Legends gratis. Hitung total diamond maksimal yang dibutuhkan untuk mendapatkan skin Legend dari event Magic Wheel MLBB.',
            'keywords' => 'kalkulator magic wheel, magic wheel mobile legends, hitung diamond magic wheel, skin legend mlbb, kalkulator diamond mobile legends',
            'canonical_url' => $canonical,
            'schema_markup' => $schema,
        ]);
    }
    
    
}

