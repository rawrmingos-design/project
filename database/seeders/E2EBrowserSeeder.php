<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class E2EBrowserSeeder extends Seeder
{
    public const POPUP_ID = 900001;

    public function run(): void
    {
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'P06 Browser Test',
                'deskripsi_web' => 'Deterministic browser test storefront.',
                'keywords' => 'browser,test',
                'logo_header' => 'assets/logo/favicon.webp',
                'logo_footer' => 'assets/logo/favicon.webp',
                'logo_favicon' => 'assets/logo/favicon.webp',
                'url_wa' => 'https://wa.me/6200000000000',
                'url_ig' => 'https://instagram.com/example',
                'url_tiktok' => 'https://tiktok.com/@example',
                'url_youtube' => 'https://youtube.com/@example',
                'url_fb' => 'https://facebook.com/example',
                'topupindo_api' => '',
                'warna1' => '#111827',
                'warna2' => '#1f2937',
                'warna3' => '#f97316',
                'warna4' => '#fb923c',
                'paydisini_apikey' => '',
                'order_prefik' => 'E2E',
                'public_theme' => 'bangjeff',
                'home_popup_enabled' => true,
                'live_sales_enabled' => false,
                'google_analytics_id' => null,
                'facebook_pixel_id' => null,
                'google_tag_manager_id' => null,
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-01 00:00:00',
            ],
        );

        DB::table('beritas')->updateOrInsert(
            ['id' => self::POPUP_ID],
            [
                'path' => null,
                'tipe' => 'popup',
                'urutan' => 0,
                'deskripsi' => '<p>Pengumuman browser test P06.</p>',
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-01 00:00:00',
            ],
        );
    }
}
