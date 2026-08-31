<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductProfitBulkUpdate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'filters' => 'array',
        'requested_profits' => 'array',
        'matched_count' => 'integer',
        'updated_count' => 'integer',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductProfitBulkUpdateItem::class, 'bulk_update_id');
    }
}
