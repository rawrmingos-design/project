<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerifiedGameAccount extends Model
{
    protected $fillable = [
        'game',
        'user_id',
        'zone_id',
        'nickname',
        'source',
    ];
}
