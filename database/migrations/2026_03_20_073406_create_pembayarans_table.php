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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id');
            $table->string('harga');
            $table->text('no_pembayaran');
            $table->string('no_pembeli', 20);
            $table->string('status');
            $table->string('metode');
            $table->string('reference')->nullable();
            $table->string('duitku_merchant_order_id')->nullable();
            $table->string('duitku_reference')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
