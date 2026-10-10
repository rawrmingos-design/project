<?php

namespace App\Console\Commands\Billing;

use App\Tenancy\BillingRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SuspendOverdueSubscriptionsCommand extends Command
{
    protected $signature = 'billing:suspend-overdue
        {--apply : Benar-benar menangguhkan tenant. Tanpa flag ini hanya melaporkan.}';

    protected $description = 'Suspend tenant yang lewat masa tenggang tanpa pembayaran (data & subdomain tetap utuh)';

    public function handle(BillingRenewalService $service): int
    {
        if (! config('billing.renewal_enabled', false)) {
            $this->warn('Penagihan otomatis dimatikan (billing.renewal_enabled = false). Tidak ada yang dikerjakan.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');

        try {
            $stats = $service->suspendOverdue($apply);
        } catch (\Throwable $e) {
            Log::error('billing:suspend-overdue gagal', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('MODE LAPORAN: tidak ada tenant yang disuspend. Tambahkan --apply untuk mengeksekusi.');
            $this->table(['Yang AKAN terjadi', 'Jumlah'], [
                ['tenant akan disuspend', (string) $stats['would_suspend']],
                ['dilewati (sudah membayar/di luar cakupan)', (string) $stats['skipped']],
            ]);

            return self::SUCCESS;
        }

        $this->info('Pemeriksaan keterlambatan selesai.');
        $this->table(['Metrik', 'Jumlah'], collect($stats)->map(fn ($n, $k) => [$k, (string) $n])->values()->all());

        return self::SUCCESS;
    }
}
