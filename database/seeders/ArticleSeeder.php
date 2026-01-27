<?php

namespace Database\Seeders;

use App\Models\Artikel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        Artikel::create([
            'title' => 'Cara Top Up Mobile Legends Murah dan Aman',
            'slug' => Str::slug('Cara Top Up Mobile Legends Murah dan Aman'),
            'thumbnail' => 'assets/banner/dummy-article.jpg', // Placeholder
            'content' => '<h2>Panduan Top Up MLBB</h2><p>Berikut adalah cara top up diamond Mobile Legends termurah...</p>',
            'meta_description' => 'Panduan lengkap cara top up diamond Mobile Legends dengan harga termurah dan proses instan.',
            'keywords' => 'top up mlbb, diamond ml murah, cara top up ml',
            'status' => 'active',
            'views' => 120,
        ]);
        
        Artikel::create([
            'title' => 'Event Promo Diamond Kuning 2026',
            'slug' => Str::slug('Event Promo Diamond Kuning 2026'),
            'thumbnail' => 'assets/banner/dummy-article-2.jpg',
            'content' => '<h2>Event Diamond Kuning Kembali!</h2><p>Jangan lewatkan event promo diamond kuning tahun ini...</p>',
            'meta_description' => 'Bocoran tanggal rilis dan cara mendapatkan diamond kuning di event Mobile Legends terbaru 2026.',
            'keywords' => 'diamond kuning ml, event ml terbaru, promo mlbb',
            'status' => 'active',
            'views' => 450,
        ]);
    }
}
