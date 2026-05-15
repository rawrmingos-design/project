<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('setting_webs') || ! Schema::hasColumn('setting_webs', 'public_theme')) {
            return;
        }

        DB::table('setting_webs')
            ->where('public_theme', 'modern')
            ->update(['public_theme' => 'bangjeff']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('setting_webs') || ! Schema::hasColumn('setting_webs', 'public_theme')) {
            return;
        }

        DB::table('setting_webs')
            ->where('public_theme', 'bangjeff')
            ->update(['public_theme' => 'modern']);
    }
};
