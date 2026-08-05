<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TikTokConversionDelivery extends Model
{
    protected $table = 'tiktok_conversion_deliveries';

    protected $guarded = [];

    protected $casts = [
        'attempts' => 'integer',
    ];

    public function pembelian(): BelongsTo
    {
        return $this->belongsTo(Pembelian::class);
    }
}
