<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_push_subscriptions', function (Blueprint $table): void {
            if (! Schema::hasColumn('public_push_subscriptions', 'session_id_hash')) {
                $table->string('session_id_hash', 64)->nullable()->after('user_id');
                $table->index('session_id_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('public_push_subscriptions', function (Blueprint $table): void {
            if (Schema::hasColumn('public_push_subscriptions', 'session_id_hash')) {
                $table->dropIndex(['session_id_hash']);
                $table->dropColumn('session_id_hash');
            }
        });
    }
};
