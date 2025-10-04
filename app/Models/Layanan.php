<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;
    public function paket()
    {
        return $this->belongsToMany(Paket::class, 'paket_layanans', 'layanan_id','paket_id');
    }
    public function kategori(){
        return $this->belongsTo(Kategori::class,'kategori_id');
    }
}
