<?php

namespace App\Console\Commands\Billing;

use App\Tenancy\BillingRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewSubscriptionsCommand extends Command
{
    protected $signature = 'billing:renew-subscriptions
        {--dry-run : Tampilkan rencana tanpa menyimpan apa pun}';

    protected $description = 'Terbitkan invoice perpanjangan (H-3) dan denda flat saat masa tenggang';

    public function handle(BillingRenewalService $service): int
    {
        if (! config('billing.renewal_enabled', false)) {
            $this->warn('Penagihan otomatis dimatikan (billing.renewal_enabled = false). Tidak ada yang dikerjakan.');

            return self::SUCCESS;
        }

        try {
            if ($this->option('dry-run')) {
                $this->info('Dry-run: tidak menyimpan perubahan. Lihat log untuk detail kandidat.');
            }

            $stats = $service->renewDueSubscriptions();
        } catch (\Throwable $e) {
            Log::error('billing:renew-subscriptions gagal', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Penagihan perpanjangan selesai.');
        $this->table(['Metrik', 'Jumlah'], collect($stats)->map(fn ($n, $k) => [$k, (string) $n])->values()->all());

        return self::SUCCESS;
    }
}
