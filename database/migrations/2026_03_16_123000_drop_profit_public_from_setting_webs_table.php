<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('setting_webs', 'profit_public')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn('profit_public');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('setting_webs', 'profit_public')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table) {
            $table->integer('profit_public')->nullable()->after('order_prefik');
        });
    }
};
