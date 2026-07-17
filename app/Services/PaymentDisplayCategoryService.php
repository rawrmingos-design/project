<?php

namespace App\Services;

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Models\TenantPaymentDisplayCategorySetting;
use App\Models\TenantPaymentMethodSetting;
use App\Support\PaymentCatalogAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PaymentDisplayCategoryService
{
    /**
     * Get visible payment display categories for the order page.
     *
     * Returns a sorted collection of visible canonical categories with their enabled methods,
     * applying tenant visibility overrides when a tenant context is active.
     */
    public function getCategoriesForOrderPage(): Collection
    {
        $tenantId = PaymentCatalogAccess::currentTenantId();

        $cacheKey = $tenantId === null
            ? "main:payment_display_categories"
            : "tenant_{$tenantId}:payment_display_categories";

        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            $categories = PaymentDisplayCategory::canonical()
                ->visible()
                ->ordered()
                ->with(['methods' => function ($query) {
                    $query->withoutGlobalScopes();
                    if (Schema::hasColumn('methods', 'tenant_id')) {
                        $query->whereNull('tenant_id');
                    }
                    $query->enabled()
                        ->orderBy('sort_order_in_category', 'asc')
                        ->orderBy('name', 'asc');
                }])
                ->get();

            if ($tenantId !== null) {
                // Apply tenant overrides
                $categoryOverrides = TenantPaymentDisplayCategorySetting::query()
                    ->where('tenant_id', $tenantId)
                    ->pluck('is_visible', 'payment_display_category_id');

                $methodOverrides = TenantPaymentMethodSetting::query()
                    ->where('tenant_id', $tenantId)
                    ->pluck('is_visible', 'method_id');

                $categories = $categories->filter(function (PaymentDisplayCategory $category) use ($categoryOverrides) {
                    return $categoryOverrides->get($category->id, true);
                })->values();

                $categories->each(function (PaymentDisplayCategory $category) use ($methodOverrides) {
                    $category->setRelation('methods', $category->methods->filter(function (Method $method) use ($methodOverrides) {
                        return $methodOverrides->get($method->id, true);
                    })->values());
                });
            }

            // Exclude categories with no effective methods
            $categories = $categories->filter(fn (PaymentDisplayCategory $category) => $category->methods->isNotEmpty());

            // Partition: SALDO-containing categories first
            $saldoCategories = $categories->filter(function (PaymentDisplayCategory $category) {
                return $category->methods->contains(fn (Method $method) => $method->isSaldoMethod());
            });

            $otherCategories = $categories->reject(function (PaymentDisplayCategory $category) {
                return $category->methods->contains(fn (Method $method) => $method->isSaldoMethod());
            });

            return $saldoCategories->values()->merge($otherCategories->values());
        });
    }

    public function invalidateCache(): void
    {
        $tenantId = PaymentCatalogAccess::currentTenantId();

        if ($tenantId === null) {
            Cache::forget("main:payment_display_categories");
            return;
        }

        Cache::forget("tenant_{$tenantId}:payment_display_categories");
    }

    public function warmCache(): void
    {
        $this->getCategoriesForOrderPage();
    }

    public function mapTipeToCategory(string $normalizedTipe): ?PaymentDisplayCategory
    {
        $normalizedTipe = Method::normalizeTipe($normalizedTipe);

        $category = PaymentDisplayCategory::canonical()
            ->where('code', $normalizedTipe)
            ->first();

        if ($category) {
            return $category;
        }

        $label = $this->mapTipeToLabel($normalizedTipe);

        if ($label === null) {
            return null;
        }

        return PaymentDisplayCategory::canonical()
            ->where('label', $label)
            ->first();
    }

    /**
     * Deprecated: Provisioning should no longer create duplicate catalog rows.
     * Kept as no-op to prevent exceptions from legacy callers (like TenantObserver).
     * If explicit overrides are needed, provision TenantPaymentDisplayCategorySetting instead.
     *
     * @param Tenant $tenant Intentionally unused — no-op preserved for backward compatibility.
     */
    public function provisionDefaultsForTenant(Tenant $tenant): void // @phpstan-ignore-line
    {
        // No-op: Canonical catalog is shared.
        // Overrides default to true, so no DB rows are needed at provision time.
        unset($tenant);
    }

    private function mapTipeToLabel(string $normalizedTipe): ?string
    {
        return match ($normalizedTipe) {
            'saldo' => 'SALDO',
            'qris' => 'QRIS',
            'bank' => 'Bank Transfer',
            'e-walet' => 'E-Wallet',
            'virtual-account' => 'Virtual Account',
            'convenience-store' => 'Convenience Store',
            default => null,
        };
    }
}
