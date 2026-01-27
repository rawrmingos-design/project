<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provider_paths', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('layanan_id');
            $table->string('provider_code'); // e.g., 'digiflazz', 'bangjeff'
            $table->string('provider_sku'); // SKU at the provider
            $table->decimal('modal_price', 15, 2)->default(0); // Price from provider
            $table->integer('priority')->default(1); // 1 = Highest
            $table->string('status')->default('available'); // available, empty, maintenance, error
            $table->timestamp('last_sync_at')->nullable();
            
            $table->foreign('layanan_id')->references('id')->on('layanans')->onDelete('cascade');
            $table->timestamps();

            // Compound index for efficient routing queries
            $table->index(['layanan_id', 'status', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_paths');
    }
};
