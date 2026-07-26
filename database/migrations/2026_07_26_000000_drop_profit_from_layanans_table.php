<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('layanans') || ! Schema::hasColumn('layanans', 'profit')) {
            return;
        }

        Schema::table('layanans', function (Blueprint $table): void {
            $table->dropColumn('profit');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('layanans') || Schema::hasColumn('layanans', 'profit')) {
            return;
        }

        Schema::table('layanans', function (Blueprint $table): void {
            $table->integer('profit')->default(0)->after('harga_flash_sale');
        });
    }
};
