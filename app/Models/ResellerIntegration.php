<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ResellerIntegration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $integration): void {
            if (blank($integration->integration_type)) {
                $integration->integration_type = 'provider';
            }

            if (blank($integration->credential_source)) {
                $integration->credential_source = 'global';
            }

            if (blank($integration->mode)) {
                $integration->mode = 'live';
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function callbackProfile(): HasOne
    {
        return $this->hasOne(ResellerCallbackProfile::class);
    }

    public function callbackDeliveries(): HasMany
    {
        return $this->hasMany(ResellerCallbackDelivery::class)->latest('id');
    }

    public function pembelians(): HasMany
    {
        return $this->hasMany(Pembelian::class);
    }
}
