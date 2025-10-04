<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhitelistedIP extends Model
{
    protected $table = 'whitelisted_ips'; // Nama tabel yang sesuai
    protected $fillable = ['ip_address']; // Kolom yang dapat diisi
}

