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
        Schema::table('provider_paths', function (Blueprint $table) {
            $table->foreign(['layanan_id'])->references(['id'])->on('layanans')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_paths', function (Blueprint $table) {
            $table->dropForeign('provider_paths_layanan_id_foreign');
        });
    }
};
