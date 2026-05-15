<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            if (! Schema::hasColumn('setting_webs', 'public_theme')) {
                $table->string('public_theme')->default('default')->after('logo_favicon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('setting_webs', function (Blueprint $table) {
            if (Schema::hasColumn('setting_webs', 'public_theme')) {
                $table->dropColumn('public_theme');
            }
        });
    }
};
