<?php

namespace App\Services;

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class PaymentDisplayCategoryService
{
    /**
     * Get visible payment display categories for the order page.
     *
     * Returns a sorted collection of visible categories with their enabled methods,
     * with SALDO-containing category always first. Uses Cache::remember() with
     * tenant-scoped key and 300s TTL.
     */
    public function getCategoriesForOrderPage(): Collection
    {
        $tenantId = $this->resolveTenantId();

        $cacheKey = $tenantId === null
            ? "main:payment_display_categories"
            : "tenant_{$tenantId}:payment_display_categories";

        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            $categories = PaymentDisplayCategory::withoutGlobalScopes()
                ->when($tenantId !== null, function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId);
                }, function ($query) {
                    $query->whereNull('tenant_id');
                })
                ->visible()
                ->ordered()
                ->with(['methods' => function ($query) use ($tenantId) {
                    $query->withoutGlobalScopes()
                        ->when(Schema::hasColumn('methods', 'tenant_id'), function ($q) use ($tenantId) {
                            if ($tenantId !== null) {
                                $q->where(function ($tenantQuery) use ($tenantId) {
                                    $tenantQuery->where('tenant_id', $tenantId)->orWhereNull('tenant_id');
                                });
                            } else {
                                $q->whereNull('tenant_id');
                            }
                        })
                        ->where('statuspayment', true)
                        ->orderBy('sort_order_in_category', 'asc')
                        ->orderBy('name', 'asc');
                }])
                ->get()
                ->filter(fn (PaymentDisplayCategory $category) => $category->methods->isNotEmpty());

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

    /**
     * Invalidate the tenant-scoped category cache.
     */
    public function invalidateCache(): void
    {
        $tenantId = $this->resolveTenantId();

        if ($tenantId === null) {
            Cache::forget("main:payment_display_categories");
            return;
        }

        Cache::forget("tenant_{$tenantId}:payment_display_categories");
    }

    /**
     * Warm the category cache by calling getCategoriesForOrderPage.
     */
    public function warmCache(): void
    {
        $this->getCategoriesForOrderPage();
    }

    /**
     * Map a normalized tipe string to the corresponding default PaymentDisplayCategory.
     */
    public function mapTipeToCategory(string $normalizedTipe): ?PaymentDisplayCategory
    {
        $tenantId = $this->resolveTenantId();

        $label = $this->mapTipeToLabel($normalizedTipe);

        if ($label === null) {
            return null;
        }

        return PaymentDisplayCategory::query()
            ->when($tenantId !== null, function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }, function ($query) {
                $query->whereNull('tenant_id');
            })
            ->where('label', $label)
            ->first();
    }

    /**
     * Provision default categories for a given tenant if they don't already exist.
     */
    public function provisionDefaultsForTenant(Tenant $tenant): void
    {
        $defaults = [
            ['label' => 'SALDO', 'display_style' => 'flat', 'sort_order' => 1],
            ['label' => 'QRIS', 'display_style' => 'flat', 'sort_order' => 2],
            ['label' => 'E-Wallet', 'display_style' => 'accordion', 'sort_order' => 3],
            ['label' => 'Virtual Account', 'display_style' => 'accordion', 'sort_order' => 4],
            ['label' => 'Convenience Store', 'display_style' => 'accordion', 'sort_order' => 5],
        ];

        foreach ($defaults as $default) {
            PaymentDisplayCategory::query()
                ->firstOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'label' => $default['label'],
                    ],
                    [
                        'display_style' => $default['display_style'],
                        'sort_order' => $default['sort_order'],
                        'is_visible' => true,
                    ]
                );
        }
    }

    /**
     * Map a normalized tipe to the corresponding default category label.
     */
    private function mapTipeToLabel(string $normalizedTipe): ?string
    {
        return match ($normalizedTipe) {
            'saldo' => 'SALDO',
            'qris' => 'QRIS',
            'e-walet' => 'E-Wallet',
            'virtual-account' => 'Virtual Account',
            'convenience-store' => 'Convenience Store',
            default => null,
        };
    }

    /**
     * Resolve the current tenant ID from the TenantContext.
     */
    private function resolveTenantId(): ?int
    {
        if (! app()->bound(TenantContext::class)) {
            return null;
        }

        $context = app(TenantContext::class);

        return $context->id();
    }
}
