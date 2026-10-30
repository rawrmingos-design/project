<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->nullable()->index();
            $table->text('description')->nullable();   // spesifikasi hadiah
            $table->string('image')->nullable();
            $table->unsignedBigInteger('point_cost');
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('weight_gram')->nullable(); // opsional, info ongkir
            $table->string('min_tier')->nullable();    // null = semua member, atau 'Gold' / 'Platinum'
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rewards');
    }
};
