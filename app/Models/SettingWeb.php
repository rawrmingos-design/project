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
        'easywa_send_delay' => 'integer',
        'captcha_enabled' => 'boolean',
        'captcha_bypass' => 'boolean',
        'seasonal_enabled' => 'boolean',
        'seasonal_starts_at' => 'datetime',
        'seasonal_ends_at' => 'datetime',
        'seasonal_background_opacity' => 'integer',
    ];
}
