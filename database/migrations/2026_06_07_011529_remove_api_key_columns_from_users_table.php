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
        Schema::table('users', function (Blueprint $table) {
            // Drop index first to avoid SQLite index errors
            if (config('database.default') !== 'sqlite') {
                $table->dropIndex('users_api_key_prefix_index');
            } else {
                // For SQLite in memory tests
                try {
                    $table->dropIndex('users_api_key_prefix_index');
                } catch (\Exception $e) {}
            }
            
            $columnsToDrop = [
                'api_key',
                'api_key_hint',
                'api_key_prefix',
                'api_key_rotated_at',
                'sandbox_api_key_hash',
                'sandbox_api_key_hint',
                'sandbox_api_key_rotated_at',
                'sandbox_api_key_last_used_at',
            ];
            
            $existingColumns = [];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $existingColumns[] = $column;
                }
            }
            
            if (!empty($existingColumns)) {
                $table->dropColumn($existingColumns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_key')->nullable()->unique();
            $table->string('api_key_hint')->nullable();
            $table->string('api_key_prefix')->nullable();
            $table->string('sandbox_api_key_hash')->nullable();
            $table->string('sandbox_api_key_hint')->nullable();
            $table->timestamp('sandbox_api_key_rotated_at')->nullable();
            $table->timestamp('sandbox_api_key_last_used_at')->nullable();
        });
    }
};
