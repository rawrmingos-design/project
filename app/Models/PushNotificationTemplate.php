<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushNotificationTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'title',
        'body',
        'details',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
