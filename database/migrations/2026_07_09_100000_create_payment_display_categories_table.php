<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_display_categories')) {
            return;
        }

        Schema::create('payment_display_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->string('label', 100);
            $table->enum('display_style', ['flat', 'accordion'])->default('flat');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->string('icon', 50)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'label'], 'payment_display_categories_tenant_label_unique');
            $table->index('tenant_id', 'payment_display_categories_tenant_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_display_categories');
    }
};
