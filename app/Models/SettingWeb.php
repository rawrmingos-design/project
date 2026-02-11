<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingWeb extends Model
{
    protected $table = 'setting_webs';
    
    protected $fillable = [
        'judul_web',
        'deskripsi_web',
        'keywords',
        'logo_header',
        'logo_footer',
        'logo_favicon',
        'url_wa',
        'url_ig',
        'url_tiktok',
        'url_youtube',
        'url_fb',
        'topupindo_api',
        'apikey_bangjeff',
        'apikey_aoshi',
        'api_mobilegamestore',
        'warna1',
        'warna2',
        'warna3',
        'warna4',
        'paydisini_apikey',
        'tripay_api',
        'tripay_merchant_code',
        'tripay_private_key',
        'tokopay_merchant_id',
        'tokopay_secret_key',
        'username_digi',
        'api_key_digi',
        'apigames_secret',
        'apigames_merchant',
        'vip_apiid',
        'vip_apikey',
        'nomor_admin',
        'wa_key',
        'wa_number',
        'ovo_admin',
        'ovo1_admin',
        'gopay_admin',
        'gopay1_admin',
        'dana_admin',
        'shopeepay_admin',
        'bca_admin',
        'order_prefik',
        'profit_public',
        'profit_member',
        'profit_platinum',
        'profit_gold',
        'google_analytics_id',
        'facebook_pixel_id',
        'google_tag_manager_id',
        'trx_count_gold',
        'trx_count_platinum',
        'commission_percent',
    ];
    
    protected $casts = [
        'profit_public' => 'integer',
        'profit_member' => 'integer',
        'profit_platinum' => 'integer',
        'profit_gold' => 'integer',
        'commission_percent' => 'integer',
    ];
}
