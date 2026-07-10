<?php

namespace App\Models;

use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Method extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted(): void
    {
        $invalidateCategoryCache = static function (self $model): void {
            $tenantId = $model->tenant_id
                ?? (app()->bound(TenantContext::class) ? app(TenantContext::class)->id() : null);

            if ($tenantId) {
                Cache::forget("tenant_{$tenantId}:payment_display_categories");
            }
        };

        static::saved(function (self $model) use ($invalidateCategoryCache): void {
            static::flushFrontendCaches();
            $invalidateCategoryCache($model);
        });

        static::deleted(function (self $model) use ($invalidateCategoryCache): void {
            static::flushFrontendCaches();
            $invalidateCategoryCache($model);
        });
    }

    public function displayCategory(): BelongsTo
    {
        return $this->belongsTo(PaymentDisplayCategory::class, 'payment_display_category_id');
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

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->whereNull('statuspayment')
                ->orWhere('statuspayment', true);
        });
    }

    public function isSaldoMethod(): bool
    {
        $code = Str::upper(trim((string) ($this->getRawOriginal('code') ?? $this->code)));
        if ($code === 'SALDO') {
            return true;
        }

        $name = Str::lower(trim((string) ($this->getRawOriginal('name') ?? $this->name)));

        return str_contains($name, 'saldo');
    }

    public function isDemoMethod(): bool
    {
        $code = Str::lower(trim((string) ($this->getRawOriginal('code') ?? $this->code)));
        $name = Str::lower(trim((string) ($this->getRawOriginal('name') ?? $this->name)));
        $payment = Str::lower(trim((string) ($this->getRawOriginal('payment') ?? $this->payment)));

        return str_contains($code, 'demo')
            || str_contains($name, 'demo')
            || str_contains($payment, 'demo');
    }

    public static function availableForDeposit(bool $allowDemoInLocal = false): Collection
    {
        return static::query()
            ->enabled()
            ->orderBy('id')
            ->get()
            ->reject(function (Method $method) use ($allowDemoInLocal): bool {
                if ($method->isSaldoMethod()) {
                    return true;
                }

                return ! $allowDemoInLocal && $method->isDemoMethod();
            })
            ->values();
    }
}
