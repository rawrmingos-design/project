<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'api_key_hint')) {
                $table->string('api_key_hint', 32)->nullable()->after('api_key');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::table('users')
                ->whereNotNull('api_key')
                ->where('api_key', '!=', '')
                ->whereNull('api_key_hint')
                ->update(['api_key_hint' => DB::raw("'...' || SUBSTR(api_key, -6)")]);
        } else {
            DB::table('users')
                ->whereNotNull('api_key')
                ->where('api_key', '!=', '')
                ->whereNull('api_key_hint')
                ->update(['api_key_hint' => DB::raw("CONCAT('...', RIGHT(api_key, 6))")]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'api_key_hint')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('api_key_hint');
        });
    }
};
