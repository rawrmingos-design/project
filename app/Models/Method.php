<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Method extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saved(fn (): bool => static::flushFrontendCaches());
        static::deleted(fn (): bool => static::flushFrontendCaches());
    }

    public function getTipeAttribute($value): mixed
    {
        return static::normalizeTipe($value);
    }

    public function setTipeAttribute($value): void
    {
        $this->attributes['tipe'] = static::normalizeTipe($value);
    }

    public function getImagesAttribute($value): mixed
    {
        if (blank($value)) {
            return $value;
        }

        return '/' . ltrim((string) $value, '/');
    }

    public function setImagesAttribute($value): void
    {
        if (blank($value)) {
            $this->attributes['images'] = $value;

            return;
        }

        $this->attributes['images'] = '/' . ltrim((string) $value, '/');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (blank($this->images)) {
            return null;
        }

        return asset(ltrim((string) $this->images, '/'));
    }

    public function getIsEnabledAttribute(): bool
    {
        return (bool) $this->statuspayment;
    }

    public static function normalizeTipe($value): mixed
    {
        return match (trim((string) $value)) {
            'ewallet', 'e-wallet', 'e_walet', 'e_wallet' => 'e-walet',
            'virtual_account' => 'virtual-account',
            'convenience_store' => 'convenience-store',
            default => $value,
        };
    }

    public function isType(string|array $types): bool
    {
        $normalizedCurrent = static::normalizeTipe($this->getRawOriginal('tipe') ?: $this->tipe);

        foreach ((array) $types as $type) {
            if ($normalizedCurrent === static::normalizeTipe($type)) {
                return true;
            }
        }

        return false;
    }

    public function isEnabled(): bool
    {
        return $this->is_enabled;
    }

    public static function flushFrontendCaches(): bool
    {
        foreach ([
            'payment_methods',
            'payment_methods_all',
            'payment_methods_price_calc',
            'payment_methods_all_api',
        ] as $key) {
            Cache::forget($key);
        }

        return true;
    }
}
