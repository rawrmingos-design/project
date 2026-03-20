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
            $table->string('thumbnail')->nullable();
            $table->string('banner')->nullable();
            $table->string('tipe')->default('game');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('schema_markup')->nullable();
            $table->boolean('server_id')->default(false);
            $table->boolean('require_user_id')->default(true);
            $table->text('deskripsi_game')->nullable();
            $table->text('deskripsi_field')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('category_type_id')->nullable()->index('kategoris_category_type_id_foreign');
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
