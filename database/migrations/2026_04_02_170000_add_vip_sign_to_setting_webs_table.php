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

        if (Schema::hasColumn('setting_webs', 'vip_sign')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table) {
            $table->text('vip_sign')->nullable()->after('vip_apikey');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs')) {
            return;
        }

        if (! Schema::hasColumn('setting_webs', 'vip_sign')) {
            return;
        }

        Schema::table('setting_webs', function (Blueprint $table) {
            $table->dropColumn('vip_sign');
        });
    }
};

