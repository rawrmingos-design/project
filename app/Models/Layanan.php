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
    public function pembelians()
    {
        return $this->hasMany(Pembelian::class, 'layanan', 'layanan'); // Assuming foreign key is 'layanan' (name) or 'layanan_id'? 
        // Wait, Pembelian likely stores 'layanan' name if historical, or 'layanan_id'.
        // I need to check Pembelian model or database schema. 
        // Usually it's id. But original store method often fetched layanan name.
        // Let's check Pembelian.
    }
    public function provider_paths()
    {
        return $this->hasMany(ProviderPath::class);
    }
}
