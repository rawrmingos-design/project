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
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->boolean('aktif_footer_beranda')->default(false)->after('deskripsi_web');
            $table->longText('deskripsi_footer_beranda')->nullable()->after('aktif_footer_beranda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn(['aktif_footer_beranda', 'deskripsi_footer_beranda']);
        });
    }
};
