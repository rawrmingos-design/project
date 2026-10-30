<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('reward_id')->nullable()->index();
            $table->string('reward_name');              // snapshot nama hadiah
            $table->unsignedBigInteger('points_spent');
            $table->string('status')->default('pending')->index();

            // alamat pengiriman
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('address');
            $table->string('city');
            $table->string('province')->nullable();
            $table->string('postal_code')->nullable();

            // data pengiriman
            $table->string('courier')->nullable();
            $table->string('tracking_no')->nullable();
            $table->text('admin_note')->nullable();

            // jejak waktu status
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('reward_id')->references('id')->on('rewards')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_redemptions');
    }
};
