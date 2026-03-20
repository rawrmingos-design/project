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
        if (Schema::hasTable('layanans')) {
            return;
        }

Schema::create('layanans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kategori_id');
            $table->string('layanan');
            $table->string('provider_id');
            $table->bigInteger('harga');
            $table->bigInteger('harga_member');
            $table->bigInteger('harga_platinum');
            $table->bigInteger('harga_gold');
            $table->bigInteger('harga_flash_sale')->nullable()->default(0);
            $table->integer('profit_member');
            $table->integer('profit_platinum');
            $table->integer('profit_gold');
            $table->tinyInteger('is_flash_sale')->default(0);
            $table->text('judul_flash_sale')->nullable();
            $table->text('banner_flash_sale')->nullable();
            $table->integer('stock_flash_sale')->nullable();
            $table->dateTime('expired_flash_sale')->nullable();
            $table->longText('catatan')->nullable();
            $table->string('status');
            $table->string('provider');
            $table->string('product_logo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('layanans');
    }
};
