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
        if (Schema::hasTable('vouchers')) {
            return;
        }

Schema::create('vouchers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('kode');
            $table->integer('promo');
            $table->integer('stock');
            $table->integer('mintrx');
            $table->integer('max_potongan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
