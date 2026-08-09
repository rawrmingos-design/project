<?php

namespace App\Models;

use App\Support\ProviderRetirement;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $fillable = [
        'code',
        'name',
        'api_endpoint',
        'balance',
        'is_active',
        'last_check_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'last_check_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Provider $provider): void {
            if (ProviderRetirement::isRetired($provider->code) && $provider->is_active) {
                throw new \DomainException('Retired providers cannot be activated.');
            }
        });
    }

    public function scopeRoutable($query)
    {
        $retiredCodes = ProviderRetirement::retiredCodes();

        return $query
            ->where('is_active', true)
            ->when($retiredCodes !== [], fn ($query) => $query->whereNotIn(
                \Illuminate\Support\Facades\DB::raw('LOWER(code)'),
                $retiredCodes,
            ));
    }

    public static function routableOptions(): array
    {
        return static::query()
            ->routable()
            ->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();
    }
}
