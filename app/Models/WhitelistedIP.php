<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhitelistedIP extends Model
{
    protected $table = 'whitelisted_ips';
    protected $fillable = ['ip_address'];
}

