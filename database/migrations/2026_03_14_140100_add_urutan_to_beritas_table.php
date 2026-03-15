<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->default(0)->after('tipe');
            $table->index(['tipe', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            $table->dropIndex(['tipe', 'urutan']);
            $table->dropColumn('urutan');
        });
    }
};
