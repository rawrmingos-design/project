<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Berita;
class HitungwrController extends Controller
{
    public function create()
    {
        $siteName  = 'ISTANATOPUP';
        $canonical = url('/id/calculator/winrate');

        $faq = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => 'Bagaimana cara menghitung win rate Mobile Legends?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Win rate dihitung dari jumlah pertandingan yang dimenangkan dibagi total pertandingan dikali 100%. Gunakan Kalkulator Win Rate ISTANATOPUP untuk mengetahui berapa kemenangan beruntun tanpa kalah yang dibutuhkan agar mencapai target win rate yang diinginkan.'],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Berapa match yang dibutuhkan untuk menaikkan win rate di MLBB?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Jumlahnya tergantung total pertandingan dan win rate saat ini. Masukkan total match, win rate sekarang, dan target win rate ke kalkulator, lalu klik Hitung untuk melihat estimasi kemenangan beruntun yang diperlukan.'],
                ],
                [
                    '@type' => 'Question',
                    'name' => 'Apakah Kalkulator Win Rate ini gratis?',
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Ya, Kalkulator Win Rate Mobile Legends dari ISTANATOPUP 100% gratis dan dapat digunakan kapan saja tanpa perlu login.'],
                ],
            ],
        ];

        $app = [
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => 'Kalkulator Win Rate Mobile Legends',
            'url' => $canonical,
            'applicationCategory' => 'UtilitiesApplication',
            'operatingSystem' => 'All',
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'IDR'],
            'publisher' => ['@type' => 'Organization', 'name' => $siteName, 'url' => url('/id')],
        ];

        $schema = '<script type="application/ld+json">'.json_encode($faq, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>'
                .'<script type="application/ld+json">'.json_encode($app, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>';

        return view('template.hitungwr', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'title' => 'Kalkulator Win Rate Mobile Legends (MLBB) - '.$siteName,
            'meta_description' => 'Kalkulator win rate Mobile Legends gratis. Hitung berapa kemenangan beruntun (tanpa kalah) yang kamu butuhkan untuk mencapai target win rate MLBB. Cepat, mudah, dan akurat.',
            'keywords' => 'kalkulator win rate mobile legends, kalkulator winrate mlbb, cara menaikkan win rate ml, hitung win rate mobile legends, win rate calculator mlbb',
            'canonical_url' => $canonical,
            'schema_markup' => $schema,
        ]);
    }
    
    
}

