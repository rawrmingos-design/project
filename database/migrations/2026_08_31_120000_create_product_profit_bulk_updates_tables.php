<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_profit_bulk_updates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('scope_type', 20);
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->json('filters')->nullable();
            $table->json('requested_profits');
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->timestamps();

            $table->index(['scope_type', 'kategori_id']);
        });

        Schema::create('product_profit_bulk_update_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bulk_update_id')->constrained('product_profit_bulk_updates')->cascadeOnDelete();
            $table->foreignId('layanan_id')->constrained('layanans')->cascadeOnDelete();
            $table->json('before_values');
            $table->json('after_values');
            $table->timestamps();

            $table->unique(['bulk_update_id', 'layanan_id'], 'bulk_profit_update_item_unique');
            $table->index('layanan_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_profit_bulk_update_items');
        Schema::dropIfExists('product_profit_bulk_updates');
    }
};
