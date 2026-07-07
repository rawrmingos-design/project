<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $query): void {
            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                return;
            }

            $query->where($query->getModel()->getTable() . '.tenant_id', $tenantId);
        });

        static::creating(function ($model): void {
            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $tenantId = app(TenantContext::class)->id();

            if ($tenantId !== null && blank($model->tenant_id)) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, Tenant|int $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;

        return $query->withoutGlobalScope('tenant')->where($query->getModel()->getTable() . '.tenant_id', $tenantId);
    }
}
