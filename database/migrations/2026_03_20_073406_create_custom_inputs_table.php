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
        Schema::create('custom_inputs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kategori_id');
            $table->string('field_1');
            $table->string('field_2')->nullable();
            $table->string('field_select_title', 5000)->nullable();
            $table->string('field_select', 5000)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_inputs');
    }
};
