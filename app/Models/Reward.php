<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    protected $guarded = [];

    protected $casts = [
        'point_cost'  => 'integer',
        'stock'       => 'integer',
        'weight_gram' => 'integer',
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
        'starts_at'   => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    /**
     * Apakah hadiah ini sedang bisa ditukar (aktif, ada stok, dalam periode).
     */
    public function isAvailable(): bool
    {
        if (!$this->is_active || $this->stock <= 0) {
            return false;
        }

        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }
}
