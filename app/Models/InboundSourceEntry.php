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
            $entry->value_type = static::detectValueType($entry->value)
                ?? strtolower(trim((string) $entry->value_type));
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

    public static function detectValueType(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, '/')) {
            [$ip, $prefix] = array_pad(explode('/', $value, 2), 2, null);
            $normalizedIp = filter_var($ip, FILTER_VALIDATE_IP);

            if ($normalizedIp === false || $prefix === null || $prefix === '' || ! ctype_digit($prefix)) {
                return null;
            }

            $prefixLength = (int) $prefix;
            $isIpv4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
            $isIpv6 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;

            if ($isIpv4 && $prefixLength >= 0 && $prefixLength <= 32) {
                return 'cidr_ipv4';
            }

            if ($isIpv6 && $prefixLength >= 0 && $prefixLength <= 128) {
                return 'cidr_ipv6';
            }

            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return 'ipv4';
        }

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return 'ipv6';
        }

        return null;
    }

    private function flushPolicyCache(): void
    {
        $policy = $this->relationLoaded('policy')
            ? $this->getRelation('policy')
            : InboundSourcePolicy::query()->find($this->policy_id);

        $policy?->flushCache();
    }
}
