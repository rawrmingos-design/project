<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentDisplayCategory extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'code',
        'label',
        'display_style',
        'sort_order',
        'is_visible',
        'icon',
        'tenant_id',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        $invalidateCache = static function (self $model): void {
            $tenantId = $model->tenant_id;

            if ($tenantId === null) {
                Cache::forget("main:payment_display_categories");
            } else {
                Cache::forget("tenant_{$tenantId}:payment_display_categories");
            }
        };

        static::saving(function (self $model): void {
            if (! Schema::hasColumn($model->getTable(), 'code')) {
                return;
            }

            $model->code = static::normalizeCode($model->code, $model->label);
        });

        static::created($invalidateCache);
        static::updated($invalidateCache);
        static::deleted($invalidateCache);
    }

    public function tenantSettings(): HasMany
    {
        return $this->hasMany(TenantPaymentDisplayCategorySetting::class, 'payment_display_category_id');
    }

    public function scopeCanonical(Builder $query): Builder
    {
        return $query->withoutGlobalScopes()->whereNull('tenant_id');
    }

    public static function normalizeCode(mixed $value, ?string $fallback = null): string
    {
        $candidate = trim((string) ($value ?? $fallback ?? ''));

        if ($candidate === '') {
            $candidate = 'payment-category';
        }

        $slug = Str::slug($candidate, '-') ?: 'payment-category';

        return match ($slug) {
            'e-wallet', 'ewallet' => 'e-walet',
            'bank-transfer' => 'bank',
            default => $slug,
        };
    }

    // Relationships

    public function methods(): HasMany
    {
        return $this->hasMany(Method::class, 'payment_display_category_id');
    }

    public function activeMethods(): HasMany
    {
        return $this->hasMany(Method::class, 'payment_display_category_id')
            ->where('statuspayment', true);
    }

    // Scopes

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'asc');
    }
}
