<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingWebsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('setting_webs')->truncate();

        // NOTE (OSS safety): seluruh kredensial provider/payment di bawah ini
        // sengaja dikosongkan ('' atau placeholder non-rahasia). Isi melalui
        // Admin Panel -> Pengaturan (tabel setting_webs) atau environment
        // deployment. Jangan pernah menaruh kredensial asli pada seeder/commit.
        DB::table('setting_webs')->insert([
        [
            'id' => 1,
            'judul_web' => 'Egy Market',
            'deskripsi_web' => 'Egy Market Digital Solutions.',
            'keywords' => 'egystore,egystore topup,egystore game,topup game murah egystore,beli diamond murah egystore,topup game termurah,harga diamond murah,voucher game diskon,promo topup game,topup aman harga terjangkau,situs topup game terpercaya,topup game aman dan cepat,egystore garansi resmi',
            'logo_header' => 'assets/logo/01KGSN7TWDAQXP947X0GH07TDE.webp',
            'logo_footer' => 'assets/logo/01KGSN7TXFTHQYY8T2SM6HQ6S2.png',
            'logo_favicon' => 'assets/logo/01KGSR2RX28HCH8084K9YB7AP2.ico',
            'url_wa' => 'https://wa.me/6281000000001',
            'url_ig' => 'https://www.instagram.com/egymaulana1404',
            'url_tiktok' => 'https://www.tiktok.com/',
            'url_youtube' => 'https://www.youtube.com/',
            'url_fb' => 'https://www.facebook.com/',
            'topupindo_api' => ' ',
            'apikey_bangjeff' => ' ',
            'apikey_aoshi' => ' ',
            'api_mobilegamestore' => ' ',
            'warna1' => '#222222',
            'warna2' => '#d06800',
            'warna3' => '#ffa54a',
            'warna4' => '#ff8040',
            'paydisini_apikey' => ' ',
            'tripay_api' => '',
            'tripay_merchant_code' => '',
            'tripay_private_key' => '',
            'duitku_merchant_code' => '',
            'duitku_merchant_key' => '',
            'duitku_callback_url' => 'https://istanatopup.imhaf.online',
            'duitku_return_url' => null,
            'duitku_mode' => 'sandbox',
            'deposit_jalur' => 'tripay',
            'duitku_enabled' => 1,
            'tokopay_merchant_id' => '',
            'tokopay_secret_key' => '',
            'username_digi' => '',
            'api_key_digi' => '',
            'apigames_secret' => '-',
            'apigames_merchant' => '-',
            'vip_apiid' => ' ',
            'vip_apikey' => ' ',
            'nomor_admin' => '6287780901780',
            'wa_key' => '',
            'wa_number' => '6287780901780',
            'ovo_admin' => '0',
            'ovo1_admin' => '0',
            'gopay_admin' => '0',
            'gopay1_admin' => '0',
            'dana_admin' => '0',
            'shopeepay_admin' => '0',
            'bca_admin' => '0',
            'order_prefik' => 'EM',
            'commission_percent' => 20,
            'profit_member' => 10,
            'profit_platinum' => 10,
            'profit_gold' => 10,
            'trx_count_gold' => 50,
            'trx_count_platinum' => 100,
            'created_at' => '2025-08-15 17:10:29',
            'updated_at' => '2026-02-21 18:11:46',
            'google_analytics_id' => 'G-aafafavadf',
            'facebook_pixel_id' => '12153135135',
            'google_tag_manager_id' => 'asafasf'
        ],
        ]);
    }
}
