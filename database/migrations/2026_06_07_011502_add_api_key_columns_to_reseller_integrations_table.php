<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reseller_integrations', function (Blueprint $table) {
            $table->string('api_key_hash', 64)->nullable()->index()->after('mode');
            $table->string('api_key_hint')->nullable()->after('api_key_hash');
            $table->string('api_key_prefix')->nullable()->after('api_key_hint');
            $table->timestamp('api_key_last_used_at')->nullable()->after('api_key_prefix');
            $table->timestamp('api_key_rotated_at')->nullable()->after('api_key_last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reseller_integrations', function (Blueprint $table) {
            $table->dropColumn([
                'api_key_hash',
                'api_key_hint',
                'api_key_prefix',
                'api_key_last_used_at',
                'api_key_rotated_at',
            ]);
        });
    }
};
