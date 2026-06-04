<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds api_key_prefix to support bcrypt-based API key verification.
 *
 * Pattern: store first 8 chars of raw key as plain-text prefix (indexed for
 * fast lookup), then use Hash::check against the bcrypt-hashed api_key column.
 * This avoids a full-table scan while keeping the raw key secure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // First 8 characters of the raw API key — plain text, used for fast indexed lookup
            $table->string('api_key_prefix', 16)->nullable()->after('api_key_hint')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['api_key_prefix']);
            $table->dropColumn('api_key_prefix');
        });
    }
};
