<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class TenantPaymentMethodSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'method_id',
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

    public function method(): BelongsTo
    {
        return $this->belongsTo(Method::class);
    }
}
