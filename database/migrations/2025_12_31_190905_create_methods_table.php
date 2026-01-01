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
        Schema::create('methods', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('name', 55);
            $table->string('images', 250);
            $table->string('code', 100);
            $table->string('keterangan', 250);
            $table->string('tipe', 225);
            $table->string('payment');
            $table->decimal('fee_percent', 5)->nullable();
            $table->decimal('fix_fee', 10)->nullable();
            $table->integer('min_pembelian')->nullable();
            $table->integer('max_pembelian')->nullable();
            $table->boolean('statuspayment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('methods');
    }
};
