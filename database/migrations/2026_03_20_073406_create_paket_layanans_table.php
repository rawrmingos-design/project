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
        if (Schema::hasTable('paket_layanans')) {
            return;
        }

Schema::create('paket_layanans', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('paket_id')->index('paket_id');
            $table->unsignedInteger('layanan_id')->index('layanan_id');
            $table->string('product_logo', 225)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paket_layanans');
    }
};
