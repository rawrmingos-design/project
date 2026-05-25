<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class InboundSourcePolicy extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $policy): void {
            $policy->source_domain = static::normalizeKeyPart($policy->source_domain);
            $policy->source_name = static::normalizeKeyPart($policy->source_name);
            $policy->mode = static::normalizeMode($policy->mode);
        });

        static::saved(function (self $policy): void {
            $policy->flushOriginalCache();
            $policy->flushCache();
        });

        static::deleted(function (self $policy): void {
            $policy->flushCache();
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(InboundSourceEntry::class, 'policy_id')->orderBy('id');
    }

    public static function resolveCached(string $sourceDomain, string $sourceName): ?self
    {
        $sourceDomain = static::normalizeKeyPart($sourceDomain);
        $sourceName = static::normalizeKeyPart($sourceName);

        return Cache::remember(
            static::cacheKey($sourceDomain, $sourceName),
            (int) config('inbound_whitelist.cache_ttl_seconds', 300),
            fn (): ?self => static::query()
                ->with([
                    'entries' => fn ($query) => $query->where('is_active', true),
                ])
                ->where('source_domain', $sourceDomain)
                ->where('source_name', $sourceName)
                ->first()
        );
    }

    public function flushCache(): void
    {
        Cache::forget(static::cacheKey($this->source_domain, $this->source_name));
    }

    public function flushOriginalCache(): void
    {
        $originalDomain = $this->getOriginal('source_domain');
        $originalName = $this->getOriginal('source_name');

        if ($originalDomain && $originalName) {
            Cache::forget(static::cacheKey($originalDomain, $originalName));
        }
    }

    public static function cacheKey(string $sourceDomain, string $sourceName): string
    {
        return sprintf(
            '%s:%s:%s',
            config('inbound_whitelist.cache_prefix', 'inbound_whitelist'),
            static::normalizeKeyPart($sourceDomain),
            static::normalizeKeyPart($sourceName),
        );
    }

    public static function normalizeMode(?string $mode): string
    {
        $value = strtolower(trim((string) $mode));

        return in_array($value, ['disabled', 'log_only', 'enforce'], true)
            ? $value
            : (string) config('inbound_whitelist.default_mode', 'log_only');
    }

    public static function normalizeKeyPart(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
