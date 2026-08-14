<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'whatsapp_verified_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('whatsapp_verified_at')->nullable()->after('no_wa');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'whatsapp_verified_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('whatsapp_verified_at');
            });
        }
    }
};
