<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produk extends Model
{
    use HasFactory;
    
    protected $table = 'layanans';
    
    protected $guarded = [];
    
    protected $casts = [
        'is_flash_sale' => 'boolean',
        'expired_flash_sale' => 'datetime',
        'harga' => 'integer',
        'harga_member' => 'integer',
        'harga_platinum' => 'integer',
        'harga_gold' => 'integer',
        'harga_flash_sale' => 'integer',
        'profit' => 'integer',
        'profit_member' => 'integer',
        'profit_platinum' => 'integer',
        'profit_gold' => 'integer',
        'stock_flash_sale' => 'integer',
    ];
    
    // Relationships
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
    
    public function scopeFlashSale($query)
    {
        return $query->where('is_flash_sale', true)
                    ->where('expired_flash_sale', '>', now());
    }
    
    // Accessors
    public function getFormattedHargaAttribute()
    {
        return 'Rp ' . number_format($this->harga, 0, ',', '.');
    }
    
    public function getIsFlashSaleActiveAttribute()
    {
        return $this->is_flash_sale && 
               $this->expired_flash_sale && 
               $this->expired_flash_sale > now();
    }

    public function provider_paths()
    {
        return $this->hasMany(ProviderPath::class, 'layanan_id');
    }
}
