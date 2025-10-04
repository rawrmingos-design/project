<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paket extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function layanan()
    {
        return $this->belongsToMany(Layanan::class, 'paket_layanans',  'paket_id','layanan_id');
    }
}
