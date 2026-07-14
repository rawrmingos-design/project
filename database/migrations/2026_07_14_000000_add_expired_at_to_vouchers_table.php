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
        if (! Schema::hasTable('vouchers') || Schema::hasColumn('vouchers', 'expired_at')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dateTime('expired_at')->nullable()->after('max_potongan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('vouchers') || ! Schema::hasColumn('vouchers', 'expired_at')) {
            return;
        }

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropColumn('expired_at');
        });
    }
};
