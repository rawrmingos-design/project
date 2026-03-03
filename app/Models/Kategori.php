<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kategori extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'server_id' => 'boolean',
        'require_user_id' => 'boolean',
    ];
    
    // Relationships
    public function layanans(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }
    
    public function products(): HasMany
    {
        return $this->hasMany(Produk::class, 'kategori_id');
    }

    public function categoryType()
    {
        return $this->belongsTo(CategoryType::class);
    }
}
