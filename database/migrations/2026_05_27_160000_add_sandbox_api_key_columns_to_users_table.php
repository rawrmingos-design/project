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
            if (! Schema::hasColumn('users', 'sandbox_api_key_hash')) {
                $table->text('sandbox_api_key_hash')->nullable()->after('api_key');
            }

            if (! Schema::hasColumn('users', 'sandbox_api_key_hint')) {
                $table->string('sandbox_api_key_hint', 32)->nullable()->after('sandbox_api_key_hash');
            }

            if (! Schema::hasColumn('users', 'sandbox_api_key_rotated_at')) {
                $table->timestamp('sandbox_api_key_rotated_at')->nullable()->after('sandbox_api_key_hint');
            }

            if (! Schema::hasColumn('users', 'sandbox_api_key_last_used_at')) {
                $table->timestamp('sandbox_api_key_last_used_at')->nullable()->after('sandbox_api_key_rotated_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            foreach ([
                'sandbox_api_key_last_used_at',
                'sandbox_api_key_rotated_at',
                'sandbox_api_key_hint',
                'sandbox_api_key_hash',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
