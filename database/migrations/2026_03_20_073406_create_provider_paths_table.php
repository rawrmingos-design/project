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
            $table->bigIncrements('id');
            $table->unsignedBigInteger('layanan_id');
            $table->string('provider_code');
            $table->string('provider_sku');
            $table->decimal('modal_price', 15)->default(0);
            $table->integer('priority')->default(1);
            $table->string('status')->default('available');
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

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
