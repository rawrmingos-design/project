<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SettingWeb extends Model
{
    protected $table = 'setting_webs';
    
    protected $guarded = [];
    
    protected $casts = [
        'profit_member' => 'integer',
        'profit_platinum' => 'integer',
        'profit_gold' => 'integer',
        'commission_percent' => 'integer',
        'mail_port' => 'integer',
        'invoice_notify_via_whatsapp' => 'boolean',
        'invoice_notify_via_email' => 'boolean',
        'affiliate_notify_via_whatsapp' => 'boolean',
        'affiliate_notify_via_email' => 'boolean',
        'tenant_notify_via_whatsapp' => 'boolean',
        'tenant_notify_via_email' => 'boolean',
        'home_popup_enabled' => 'boolean',
        'live_sales_enabled' => 'boolean',
        'easywa_send_delay' => 'integer',
        'captcha_enabled' => 'boolean',
        'captcha_bypass' => 'boolean',
        'seasonal_enabled' => 'boolean',
        'seasonal_starts_at' => 'datetime',
        'seasonal_ends_at' => 'datetime',
        'seasonal_background_opacity' => 'integer',
        'seo_robots_enabled' => 'boolean',
        'seo_sitemap_enabled' => 'boolean',
        'seo_sitemap_include_categories' => 'boolean',
        'seo_sitemap_include_articles' => 'boolean',
        'seo_sitemap_cache_minutes' => 'integer',
        'seo_sitemap_mode' => 'string',
        'public_theme' => 'string',
        'seo_sitemap_index_asset_id' => 'integer',
        'seo_sitemap_main_asset_id' => 'integer',
        'seo_sitemap_categories_asset_id' => 'integer',
        'pwa_icon_generated_at' => 'datetime',
    ];
}
