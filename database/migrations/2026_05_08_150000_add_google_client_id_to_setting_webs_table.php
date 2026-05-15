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

        Schema::table('setting_webs', function (Blueprint $table): void {
            if (! Schema::hasColumn('setting_webs', 'google_client_id')) {
                $table->string('google_client_id', 255)->nullable()->after('google_tag_manager_id');
            }
        });
    }

    public function down(): void
    {
        // Compatibility migration for legacy schemas.
        // Column intentionally not dropped.
    }
};

