<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class PaymentDisplayCategory extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
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

        static::created($invalidateCache);
        static::updated($invalidateCache);
        static::deleted($invalidateCache);
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
