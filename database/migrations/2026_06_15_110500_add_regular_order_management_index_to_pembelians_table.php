<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'pembelians_reseller_created_id_idx';

    public function up(): void
    {
        if (! Schema::hasTable('pembelians')) {
            return;
        }

        if (
            ! Schema::hasColumn('pembelians', 'reseller_integration_id')
            || ! Schema::hasColumn('pembelians', 'created_at')
        ) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table): void {
            if (! $this->indexExists('pembelians', self::INDEX_NAME)) {
                $table->index(['reseller_integration_id', 'created_at', 'id'], self::INDEX_NAME);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('pembelians')) {
            return;
        }

        Schema::table('pembelians', function (Blueprint $table): void {
            if ($this->indexExists('pembelians', self::INDEX_NAME)) {
                $table->dropIndex(self::INDEX_NAME);
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return match (DB::getDriverName()) {
            'mysql' => DB::selectOne("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]) !== null,
            'sqlite' => collect(DB::select("PRAGMA index_list('{$table}')"))->contains(
                fn ($row) => (($row->name ?? null) === $index)
            ),
            default => false,
        };
    }
};
