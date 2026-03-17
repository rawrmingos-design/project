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
    ];
}
