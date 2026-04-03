<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'code',
        'name',
        'api_username',
        'api_key',
        'api_sign',
        'api_endpoint',
        'balance',
        'is_active',
        'last_check_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'last_check_at' => 'datetime',
    ];
}
