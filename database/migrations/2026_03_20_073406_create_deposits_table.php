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
        if (Schema::hasTable('deposits')) {
            return;
        }

Schema::create('deposits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('order_id');
            $table->string('username');
            $table->string('metode');
            $table->string('no_pembayaran');
            $table->bigInteger('jumlah');
            $table->enum('status', ['Success', 'Pending']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
