<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('affiliate_histories')) {
            return;
        }

        $this->deduplicateOrderCommissionHistory();

        if ($this->hasIndex('affiliate_histories', 'affiliate_histories_order_id_unique')) {
            return;
        }

        Schema::table('affiliate_histories', function (Blueprint $table): void {
            $table->unique('order_id', 'affiliate_histories_order_id_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('affiliate_histories')) {
            return;
        }

        if (! $this->hasIndex('affiliate_histories', 'affiliate_histories_order_id_unique')) {
            return;
        }

        Schema::table('affiliate_histories', function (Blueprint $table): void {
            $table->dropUnique('affiliate_histories_order_id_unique');
        });
    }

    private function deduplicateOrderCommissionHistory(): void
    {
        $duplicates = DB::table('affiliate_histories')
            ->select('order_id')
            ->whereNotNull('order_id')
            ->where('order_id', '<>', '')
            ->groupBy('order_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('order_id');

        foreach ($duplicates as $orderId) {
            $orderId = (string) $orderId;

            $keepId = DB::table('affiliate_histories')
                ->where('order_id', $orderId)
                ->orderBy('id')
                ->value('id');

            if (! $keepId) {
                continue;
            }

            DB::table('affiliate_histories')
                ->where('order_id', $orderId)
                ->where('id', '<>', $keepId)
                ->delete();
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            $result = DB::select(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1',
                [$table, $indexName]
            );

            return $result !== [];
        }

        if ($driver === 'pgsql') {
            $result = DB::select(
                'SELECT 1 FROM pg_indexes WHERE schemaname = current_schema() AND tablename = ? AND indexname = ? LIMIT 1',
                [$table, $indexName]
            );

            return $result !== [];
        }

        return false;
    }
};
