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
        // 1. Add affiliate_status to users
        Schema::table('users', function (Blueprint $table) {
            // 'inactive' = normal user, 'pending' = requested, 'active' = approved affiliate, 'rejected' = rejected
            if (!Schema::hasColumn('users', 'affiliate_status')) {
                $table->enum('affiliate_status', ['inactive', 'pending', 'active', 'rejected'])
                      ->default('inactive')
                      ->after('role'); // Placing it after role for logical grouping
            }
        });

        // 2. Add user_id to withdrawals
        Schema::table('withdrawals', function (Blueprint $table) {
            if (!Schema::hasColumn('withdrawals', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                // We add index for performance
                $table->index('user_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'affiliate_status')) {
                $table->dropColumn('affiliate_status');
            }
        });

        Schema::table('withdrawals', function (Blueprint $table) {
            if (Schema::hasColumn('withdrawals', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
