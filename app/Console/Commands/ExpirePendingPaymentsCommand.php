<?php

namespace App\Console\Commands;

use App\Services\Payments\ExpirePendingPayments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingPaymentsCommand extends Command
{
    protected $signature = 'payments:expire-pending
        {--batch=100 : Number of payments per chunk}
        {--limit= : Maximum payments to process}
        {--dry-run : Show candidates without updating}';

    protected $description = 'Mark unpaid payments as expired after their expiry time and sync related order status';

    public function handle(ExpirePendingPayments $service): int
    {
        $batchSize = (int) $this->option('batch');
        $limitOption = $this->option('limit');
        $limit = filled($limitOption) ? (int) $limitOption : null;
        $dryRun = (bool) $this->option('dry-run');

        if ($batchSize < 1 || $batchSize > 1000) {
            $this->error('The --batch option must be between 1 and 1000.');

            return self::FAILURE;
        }

        if ($limit !== null && $limit < 0) {
            $this->error('The --limit option must be zero or greater.');

            return self::FAILURE;
        }

        try {
            $stats = $service->expire($batchSize, $limit, $dryRun);
        } catch (\Throwable $exception) {
            Log::error('Expire pending payments command failed', [
                'error' => $exception->getMessage(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($dryRun ? 'Expired payment dry-run completed.' : 'Expired payment sync completed.');
        $this->table(['Metric', 'Count'], collect($stats)->map(fn ($count, $metric): array => [
            $metric,
            (string) $count,
        ])->values()->all());

        return self::SUCCESS;
    }
}
