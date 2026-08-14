<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('deposits')) {
            return;
        }

        Schema::table('deposits', function (Blueprint $table): void {
            if (! Schema::hasColumn('deposits', 'source')) {
                $table->string('source', 40)->nullable()->after('status');
            }

            if (! Schema::hasColumn('deposits', 'external_user_id')) {
                $table->string('external_user_id', 191)->nullable()->after('source');
            }

            if (! Schema::hasColumn('deposits', 'external_message_id')) {
                $table->string('external_message_id', 191)->nullable()->after('external_user_id');
            }

            if (! Schema::hasColumn('deposits', 'idempotency_key')) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('external_message_id');
            }

            if (! Schema::hasColumn('deposits', 'payment_metadata')) {
                $table->json('payment_metadata')->nullable()->after('idempotency_key');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('deposits')) {
            return;
        }

        $columns = array_values(array_filter([
            'source',
            'external_user_id',
            'external_message_id',
            'idempotency_key',
            'payment_metadata',
        ], static fn (string $column): bool => Schema::hasColumn('deposits', $column)));

        if ($columns === []) {
            return;
        }

        Schema::table('deposits', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
