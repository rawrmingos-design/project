<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointHistory extends Model
{
    protected $guarded = [];

    protected $casts = [
        'points' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFormattedPointsAttribute(): string
    {
        return ($this->type === 'earn' ? '+' : '-') . number_format($this->points, 0, ',', '.') . ' poin';
    }
}
