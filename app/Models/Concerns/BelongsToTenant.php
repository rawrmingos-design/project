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

            $tenant = app(TenantContext::class)->get();
            $tenantId = $tenant?->id;

            if ($tenantId === null) {
                return;
            }

            $table = $query->getModel()->getTable();

            if ($query->getModel() instanceof \App\Models\User && $tenant->owner_user_id) {
                $query->where(function (Builder $inner) use ($table, $tenantId, $tenant): void {
                    $inner->where($table . '.tenant_id', $tenantId)
                        ->orWhere($table . '.id', $tenant->owner_user_id);
                });

                return;
            }

            $query->where($table . '.tenant_id', $tenantId);
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
