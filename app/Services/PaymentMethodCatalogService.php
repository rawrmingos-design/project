<?php

namespace App\Services;

use App\Models\Method;
use App\Models\TenantPaymentMethodSetting;
use App\Support\PaymentCatalogAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PaymentMethodCatalogService
{
    /**
     * Get all visible methods for the specified tenant (or master).
     *
     * Rules:
     * - Master: methods where statuspayment is enabled.
     * - Tenant: globally enabled methods AND tenant override is not false.
     */
    public function getVisibleMethods(?int $tenantId = null): Collection
    {
        $tenantId ??= PaymentCatalogAccess::currentTenantId();

        $cacheKey = $tenantId === null
            ? "main:payment_methods_visible:v1"
            : "tenant_{$tenantId}:payment_methods_visible:v1";

        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            $methods = Method::query()
                ->when(\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id'), function ($q) {
                    $q->whereNull('tenant_id');
                })
                ->enabled() // Checks statuspayment IS NULL OR true
                ->orderBy('id')
                ->get();

            if ($tenantId === null) {
                return $methods;
            }

            $overrides = TenantPaymentMethodSetting::query()
                ->where('tenant_id', $tenantId)
                ->pluck('is_visible', 'method_id');

            return $methods->filter(function (Method $method) use ($overrides) {
                // If override exists and is false, it's hidden. Otherwise it's visible.
                return $overrides->get($method->id, true);
            })->values();
        });
    }

    /**
     * Find a visible method by code for the current tenant.
     */
    public function findVisibleByCode(string $code): ?Method
    {
        $methods = $this->getVisibleMethods();

        return $methods->firstWhere('code', $code);
    }
}
