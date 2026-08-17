<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SettingWeb extends Model
{
    protected $table = 'setting_webs';
    
    protected $guarded = [];

    protected $hidden = [
        'tiktok_access_token_encrypted',
        'telegram_bot_token',
        'telegram_webhook_secret',
    ];

    protected $casts = [
        'tiktok_tracking_enabled' => 'boolean',
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
        'bot_order_wa_enabled' => 'boolean',
        'bot_order_tg_enabled' => 'boolean',
    ];

    public function setTiktokAccessTokenAttribute(mixed $value): void
    {
        $value = filled($value) ? trim((string) $value) : null;

        if ($value !== null) {
            $this->attributes['tiktok_access_token_encrypted'] = Crypt::encryptString($value);
        }
    }

    public function decryptedTiktokAccessToken(): string
    {
        $encrypted = (string) ($this->attributes['tiktok_access_token_encrypted'] ?? '');

        if ($encrypted === '') {
            return '';
        }

        try {
            return Crypt::decryptString($encrypted);
        } catch (\Throwable) {
            return '';
        }
    }

    public function hasTiktokAccessToken(): bool
    {
        return $this->decryptedTiktokAccessToken() !== '';
    }
}
