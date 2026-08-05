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
        Schema::create('tiktok_conversion_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained('pembelians')->cascadeOnDelete();
            $table->string('event_name', 50)->default('CompletePayment');
            $table->string('pixel_id');
            $table->string('event_id');
            $table->string('delivery_status')->default('pending'); // pending, delivered, ambiguous, failed
            $table->text('last_error')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamps();

            $table->unique(
                ['pixel_id', 'event_name', 'event_id'],
                'tiktok_deliveries_event_unique',
            );
            $table->index(['pembelian_id', 'delivery_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_conversion_deliveries');
    }
};
