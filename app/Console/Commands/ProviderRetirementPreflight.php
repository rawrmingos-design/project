<?php

namespace App\Console\Commands;

use App\Support\PembelianStatus;
use App\Support\ProviderRetirement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProviderRetirementPreflight extends Command
{
    protected $signature = 'provider:retirement-preflight {--strict : Return a failure code while any retirement gate is open}';

    protected $description = 'Audit active references and queued work for retired top-up providers';

    public function handle(): int
    {
        $retiredCodes = ProviderRetirement::retiredCodes();
        $pendingLabels = array_values(array_unique(array_merge(
            PembelianStatus::pendingLabels(),
            ['Sending'],
        )));

        $metrics = [
            'active_provider_rows' => $this->countWhereCodeIn('providers', 'code', $retiredCodes, $this->scopeWhere('providers', 'is_active', true)),
            'available_legacy_layanans' => $this->countWhereCodeIn('layanans', 'provider', $retiredCodes, $this->scopeWhere('layanans', 'status', 'available')),
            'available_provider_paths' => $this->countWhereCodeIn('provider_paths', 'provider_code', $retiredCodes, $this->scopeWhereIn('provider_paths', 'status', ['active', 'available'])),
            'non_final_historical_provider_orders' => $this->countWhereCodeIn('pembelians', 'provider', $retiredCodes, $this->scopeWhereIn('pembelians', 'status', $pendingLabels)),
            'non_final_active_provider_orders' => $this->countWhereCodeIn('pembelians', 'active_provider_code', $retiredCodes, $this->scopeWhereIn('pembelians', 'status', $pendingLabels)),
            'active_reset_orders' => $this->countWhereCodeIn('pembelians', 'active_provider_code', $retiredCodes, $this->scopeWhereIn('pembelians', 'reset_status', ['requested', 'preparing', 'processing'])),
            'queued_provider_jobs' => $this->countSerializedReferences('jobs', 'payload', $retiredCodes),
            'failed_provider_jobs' => $this->countSerializedReferences('failed_jobs', 'payload', $retiredCodes),
        ];

        $this->table(
            ['Gate', 'Count', 'Status'],
            collect($metrics)->map(fn (int $count, string $metric): array => [
                $metric,
                $count,
                $count === 0 ? 'closed' : 'open',
            ])->values()->all(),
        );

        $openGates = collect($metrics)->filter(fn (int $count): bool => $count > 0);

        if ($openGates->isEmpty()) {
            $this->info('Database and queue retirement gates are closed. Verify running workers and outbound traffic separately.');

            return self::SUCCESS;
        }

        $this->warn('Provider retirement is not ready for Release B. Reconcile every open gate first.');

        return $this->option('strict') ? self::FAILURE : self::SUCCESS;
    }

    private function scopeWhere(string $table, string $column, mixed $value): callable
    {
        return static function ($query) use ($table, $column, $value): void {
            if (Schema::hasColumn($table, $column)) {
                $query->where($column, $value);
            }
        };
    }

    private function scopeWhereIn(string $table, string $column, array $values): callable
    {
        return static function ($query) use ($table, $column, $values): void {
            if (Schema::hasColumn($table, $column)) {
                $query->whereIn($column, $values);
            }
        };
    }

    private function countWhereCodeIn(string $table, string $column, array $codes, callable $scope): int
    {
        if ($codes === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        $query = DB::table($table)->whereIn(DB::raw("LOWER({$column})"), $codes);
        $scope($query);

        return $query->count();
    }

    private function countSerializedReferences(string $table, string $column, array $codes): int
    {
        if ($codes === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)
            ->where(function ($query) use ($column, $codes): void {
                foreach ($codes as $code) {
                    $query->orWhereRaw("LOWER({$column}) LIKE ?", ['%' . $code . '%']);
                }
            })
            ->count();
    }
}
