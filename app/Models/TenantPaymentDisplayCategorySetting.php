<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class TenantPaymentDisplayCategorySetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'payment_display_category_id',
        'is_visible',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    protected static function booted(): void
    {
        $invalidate = static function (self $model): void {
            Cache::forget("tenant_{$model->tenant_id}:payment_display_categories");
        };

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PaymentDisplayCategory::class, 'payment_display_category_id');
    }
}
