<?php

namespace App\Console\Commands\Billing;

use App\Tenancy\BillingRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SuspendOverdueSubscriptionsCommand extends Command
{
    protected $signature = 'billing:suspend-overdue';

    protected $description = 'Suspend tenant yang lewat masa tenggang tanpa pembayaran (data & subdomain tetap utuh)';

    public function handle(BillingRenewalService $service): int
    {
        if (! config('billing.renewal_enabled', false)) {
            $this->warn('Penagihan otomatis dimatikan (billing.renewal_enabled = false). Tidak ada yang dikerjakan.');

            return self::SUCCESS;
        }

        try {
            $stats = $service->suspendOverdue();
        } catch (\Throwable $e) {
            Log::error('billing:suspend-overdue gagal', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Pemeriksaan keterlambatan selesai.');
        $this->table(['Metrik', 'Jumlah'], collect($stats)->map(fn ($n, $k) => [$k, (string) $n])->values()->all());

        return self::SUCCESS;
    }
}
