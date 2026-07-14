<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant payment method visibility overrides.
 *
 * Master admin controls the canonical methods catalog. Non-master tenants
 * cannot create, edit, or delete canonical methods; instead they write a row
 * here to toggle whether a global method appears on their storefront checkout.
 *
 * Rules enforced at the service layer:
 *  - No row              → inherit global statuspayment (default: visible if enabled).
 *  - is_visible = false  → method hidden for this tenant even if globally enabled.
 *  - is_visible = true   → method visible for this tenant if globally enabled.
 *  - Global statuspayment = false always hides, even if tenant override = true.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_payment_method_settings')) {
            return;
        }

        Schema::create('tenant_payment_method_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            // Methods table uses INT(11) for ID, not BIGINT
            $table->integer('method_id');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->foreign('method_id')
                ->references('id')->on('methods')
                ->cascadeOnDelete();

            $table->unique(
                ['tenant_id', 'method_id'],
                'tpms_tenant_method_unique'
            );
            $table->index('tenant_id', 'tpms_tenant_id_index');
            $table->index(['tenant_id', 'is_visible'], 'tpms_tenant_visible_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_payment_method_settings');
    }
};
