<?php

use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Default category definitions: label => [display_style, sort_order]
     */
    private const DEFAULTS = [
        'SALDO' => ['flat', 1],
        'QRIS' => ['flat', 2],
        'E-Wallet' => ['accordion', 3],
        'Virtual Account' => ['accordion', 4],
        'Convenience Store' => ['accordion', 5],
    ];

    /**
     * Mapping from normalized tipe to category label.
     */
    private const TIPE_TO_LABEL = [
        'saldo' => 'SALDO',
        'qris' => 'QRIS',
        'e-walet' => 'E-Wallet',
        'virtual-account' => 'Virtual Account',
        'convenience-store' => 'Convenience Store',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('payment_display_categories') || ! Schema::hasTable('methods')) {
            return;
        }

        if (! Schema::hasColumn('methods', 'payment_display_category_id')) {
            return;
        }

        // Seed canonical defaults (null tenant_id). Tenant-specific visibility is handled
        // through override tables, so duplicating catalog rows per tenant would hide
        // categories from the public storefront.
        $this->seedCategoriesForTenant(null);
        $this->assignMethodsForTenant(null);
    }

    public function down(): void
    {
        // Data migration — no structural rollback needed.
        // Optionally null out the FK assignments, but this is intentionally left as a no-op
        // to avoid accidentally wiping manual admin assignments.
    }

    private function seedCategoriesForTenant(?Tenant $tenant): void
    {
        foreach (self::DEFAULTS as $label => [$displayStyle, $sortOrder]) {
            PaymentDisplayCategory::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id' => $tenant?->id,
                    'label' => $label,
                ],
                [
                    'display_style' => $displayStyle,
                    'sort_order' => $sortOrder,
                    'is_visible' => true,
                ]
            );
        }
    }

    private function assignMethodsForTenant(?Tenant $tenant): void
    {
        $methodsHaveTenantId = Schema::hasColumn('methods', 'tenant_id');

        if ($tenant !== null && ! $methodsHaveTenantId) {
            return;
        }

        // Build a lookup of label → category ID for this tenant
        $categories = PaymentDisplayCategory::withoutGlobalScopes()
            ->where('tenant_id', $tenant?->id)
            ->pluck('id', 'label');

        // Get all methods for this tenant that don't yet have a category assigned
        $methods = DB::table('methods')
            ->when($methodsHaveTenantId, function ($query) use ($tenant): void {
                $query->where('tenant_id', $tenant?->id);
            })
            ->whereNull('payment_display_category_id')
            ->get(['id', 'tipe', 'name']);

        foreach ($methods as $method) {
            $normalizedTipe = Method::normalizeTipe($method->tipe);
            $label = self::TIPE_TO_LABEL[$normalizedTipe] ?? null;

            if ($label === null || ! $categories->has($label)) {
                Log::warning('Payment display category migration: unmatched method', [
                    'method_id' => $method->id,
                    'method_name' => $method->name,
                    'tipe' => $method->tipe,
                    'normalized_tipe' => $normalizedTipe,
                    'tenant_id' => $tenant?->id,
                ]);

                continue;
            }

            DB::table('methods')
                ->where('id', $method->id)
                ->update(['payment_display_category_id' => $categories->get($label)]);
        }
    }
};
