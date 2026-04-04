<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provider_paths') || Schema::hasColumn('provider_paths', 'metadata')) {
            return;
        }

        Schema::table('provider_paths', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('provider_paths') || ! Schema::hasColumn('provider_paths', 'metadata')) {
            return;
        }

        Schema::table('provider_paths', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
