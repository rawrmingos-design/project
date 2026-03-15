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
        Schema::create('kategoris', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama');
            $table->string('sub_nama', 225);
            $table->string('kode')->nullable();
            $table->string('status')->default('active');
            $table->string('thumbnail');
            $table->string('banner')->nullable();
            $table->string('tipe')->default('game');
            $table->boolean('server_id')->default(false);
            $table->text('deskripsi_game')->nullable();
            $table->text('deskripsi_field')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategoris');
    }
};
