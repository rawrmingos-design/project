<?php

namespace App\Models;

use App\Support\ProviderRetirement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderPath extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (ProviderPath $providerPath): void {
            if (ProviderRetirement::isRetired($providerPath->provider_code) && $providerPath->status !== 'unavailable') {
                throw new \DomainException('Retired provider paths must remain unavailable.');
            }
        });
    }

    protected $casts = [
        'modal_price' => 'decimal:2',
        'priority' => 'integer',
        'metadata' => 'array',
        'last_sync_at' => 'datetime',
    ];

    public function layanan()
    {
        return $this->belongsTo(Layanan::class);
    }
}
