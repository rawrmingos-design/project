<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id', 191)->nullable()->after('api_key');
            }

            if (! Schema::hasColumn('users', 'google_avatar')) {
                $table->string('google_avatar', 2048)->nullable()->after('google_id');
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration for shared legacy schemas.
        // Columns are intentionally not dropped.
    }
};

