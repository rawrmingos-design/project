<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('methods') || Schema::hasColumn('methods', 'payment_display_category_id')) {
            return;
        }

        Schema::table('methods', function (Blueprint $table): void {
            $table->unsignedBigInteger('payment_display_category_id')->nullable()->after('tipe');
            $table->integer('sort_order_in_category')->default(0)->after('payment_display_category_id');
        });

        Schema::table('methods', function (Blueprint $table): void {
            $table->foreign('payment_display_category_id', 'methods_payment_display_category_fk')
                ->references('id')
                ->on('payment_display_categories')
                ->nullOnDelete();
            $table->index('payment_display_category_id', 'methods_payment_display_category_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('methods') || ! Schema::hasColumn('methods', 'payment_display_category_id')) {
            return;
        }

        Schema::table('methods', function (Blueprint $table): void {
            $table->dropIndex('methods_payment_display_category_idx');
            $table->dropForeign('methods_payment_display_category_fk');
            $table->dropColumn(['payment_display_category_id', 'sort_order_in_category']);
        });
    }
};
