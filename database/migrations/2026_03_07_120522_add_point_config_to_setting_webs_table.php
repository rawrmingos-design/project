<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            // Poin yang didapat per Rp 1.000 belanja (default: 1 poin)
            $table->unsignedInteger('point_per_nominal')->default(1)->after('commission_percent');
            // Nilai 1 poin dalam Rupiah (default: Rp 100)
            $table->unsignedInteger('point_value')->default(100)->after('point_per_nominal');
            // Batas maksimal % dari harga yang bisa dibayar dengan poin (default: 50%)
            $table->unsignedInteger('max_point_usage_percent')->default(50)->after('point_value');
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn(['point_per_nominal', 'point_value', 'max_point_usage_percent']);
        });
    }
};
