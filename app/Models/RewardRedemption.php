<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SHIPPED    = 'shipped';
    public const STATUS_COMPLETED  = 'completed';
    public const STATUS_REJECTED   = 'rejected';

    public const STATUS_LABELS = [
        self::STATUS_PENDING    => 'Menunggu Diproses',
        self::STATUS_PROCESSING => 'Diproses',
        self::STATUS_SHIPPED    => 'Dikirim',
        self::STATUS_COMPLETED  => 'Selesai',
        self::STATUS_REJECTED   => 'Ditolak',
    ];

    protected $guarded = [];

    protected $casts = [
        'points_spent' => 'integer',
        'processed_at' => 'datetime',
        'shipped_at'   => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at'  => 'datetime',
        'refunded_at'  => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst((string) $this->status);
    }
}
