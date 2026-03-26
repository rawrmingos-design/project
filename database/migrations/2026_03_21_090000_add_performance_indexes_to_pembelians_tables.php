<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pembelians')) {
            Schema::table('pembelians', function (Blueprint $table): void {
                if (! $this->indexExists('pembelians', 'pembelians_created_at_id_index')) {
                    $table->index(['created_at', 'id']);
                }

                if (! $this->indexExists('pembelians', 'pembelians_status_created_at_index')) {
                    $table->index(['status', 'created_at']);
                }
            });
        }

        if (Schema::hasTable('pembayarans')) {
            Schema::table('pembayarans', function (Blueprint $table): void {
                if (! $this->indexExists('pembayarans', 'pembayarans_order_id_index')) {
                    $table->index('order_id');
                }

                if (! $this->indexExists('pembayarans', 'pembayarans_status_index')) {
                    $table->index('status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pembelians')) {
            Schema::table('pembelians', function (Blueprint $table): void {
                if ($this->indexExists('pembelians', 'pembelians_created_at_id_index')) {
                    $table->dropIndex('pembelians_created_at_id_index');
                }

                if ($this->indexExists('pembelians', 'pembelians_status_created_at_index')) {
                    $table->dropIndex('pembelians_status_created_at_index');
                }
            });
        }

        if (Schema::hasTable('pembayarans')) {
            Schema::table('pembayarans', function (Blueprint $table): void {
                if ($this->indexExists('pembayarans', 'pembayarans_order_id_index')) {
                    $table->dropIndex('pembayarans_order_id_index');
                }

                if ($this->indexExists('pembayarans', 'pembayarans_status_index')) {
                    $table->dropIndex('pembayarans_status_index');
                }
            });
        }
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
