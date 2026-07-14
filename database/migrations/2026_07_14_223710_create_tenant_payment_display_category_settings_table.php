<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant payment display category visibility overrides.
 *
 * Master admin controls the canonical payment_display_categories catalog
 * (tenant_id = null). Non-master tenants cannot create or edit catalog rows;
 * instead they write a row here to toggle whether a global category appears
 * on their storefront.
 *
 * Rules enforced at the service layer:
 *  - No row              → inherit global is_visible (default: visible).
 *  - is_visible = true   → visible for this tenant (unless global is_visible = false).
 *  - is_visible = false  → hidden for this tenant regardless of global status.
 *  - Global is_visible = false always hides, even if tenant override = true.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_payment_display_category_settings')) {
            return;
        }

        Schema::create('tenant_payment_display_category_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('payment_display_category_id')
                ->constrained('payment_display_categories', 'id', 'tpdcs_category_id_foreign')
                ->cascadeOnDelete();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'payment_display_category_id'],
                'tpdcs_tenant_category_unique'
            );
            $table->index('tenant_id', 'tpdcs_tenant_id_index');
            $table->index(['tenant_id', 'is_visible'], 'tpdcs_tenant_visible_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payment_display_category_settings');
    }
};
