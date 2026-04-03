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

    protected $casts = [
        'kategori_id' => 'integer',
        'harga' => 'integer',
        'harga_member' => 'integer',
        'harga_platinum' => 'integer',
        'harga_gold' => 'integer',
        'harga_flash_sale' => 'integer',
        'profit_member' => 'integer',
        'profit_platinum' => 'integer',
        'profit_gold' => 'integer',
        'is_flash_sale' => 'boolean',
        'stock_flash_sale' => 'integer',
        'expired_flash_sale' => 'datetime',
    ];

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
