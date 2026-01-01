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
        Schema::create('pembelians', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id');
            $table->string('username')->nullable();
            $table->string('user_id');
            $table->string('zone')->nullable();
            $table->string('nickname')->nullable();
            $table->string('layanan');
            $table->integer('harga');
            $table->integer('profit');
            $table->string('provider_order_id')->nullable();
            $table->string('status');
            $table->string('log', 1000)->nullable();
            $table->string('voucher')->nullable();
            $table->string('tipe_transaksi')->default('game');
            $table->string('ip_address', 2225)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelians');
    }
};
