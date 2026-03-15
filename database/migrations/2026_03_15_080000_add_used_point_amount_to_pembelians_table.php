<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->unsignedBigInteger('used_point_amount')->default(0)->after('used_points');
        });
    }

    public function down(): void
    {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn('used_point_amount');
        });
    }
};
