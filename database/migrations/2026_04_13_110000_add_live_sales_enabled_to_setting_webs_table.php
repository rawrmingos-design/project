<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        if (Schema::hasColumn('setting_webs', 'live_sales_enabled')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $table->boolean('live_sales_enabled')->default(true)->after('home_popup_enabled');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        if (! Schema::hasColumn('setting_webs', 'live_sales_enabled')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table): void {
            $table->dropColumn('live_sales_enabled');
        });
    }
};
