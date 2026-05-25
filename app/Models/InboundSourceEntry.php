<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboundSourceEntry extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $entry): void {
            $entry->value = trim((string) $entry->value);
            $entry->value_type = strtolower(trim((string) $entry->value_type));
        });

        static::saved(function (self $entry): void {
            $entry->flushPolicyCache();
        });

        static::deleted(function (self $entry): void {
            $entry->flushPolicyCache();
        });
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(InboundSourcePolicy::class, 'policy_id');
    }

    private function flushPolicyCache(): void
    {
        $policy = $this->relationLoaded('policy')
            ? $this->getRelation('policy')
            : InboundSourcePolicy::query()->find($this->policy_id);

        $policy?->flushCache();
    }
}
