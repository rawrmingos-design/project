<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Layanan extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;

    public function paket(): BelongsToMany
    {
        return $this->belongsToMany(Paket::class, 'paket_layanans', 'layanan_id', 'paket_id')
            ->withPivot('product_logo')
            ->withTimestamps();
    }

    // Filament AttachAction pada relation manager `layanan` mencari inverse `pakets()`.
    public function pakets(): BelongsToMany
    {
        return $this->paket();
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function pembelians(): HasMany
    {
        return $this->hasMany(Pembelian::class, 'layanan', 'layanan');
    }

    public function provider_paths(): HasMany
    {
        return $this->hasMany(ProviderPath::class);
    }
}
