<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductProfitBulkUpdateItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
    ];

    public function bulkUpdate(): BelongsTo
    {
        return $this->belongsTo(ProductProfitBulkUpdate::class, 'bulk_update_id');
    }

    public function layanan(): BelongsTo
    {
        return $this->belongsTo(Layanan::class, 'layanan_id');
    }
}
