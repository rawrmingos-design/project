<?php

namespace App\Console\Commands\Billing;

use App\Services\Settings\DatabaseSettingsBridge;
use App\Tenancy\BillingRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RenewSubscriptionsCommand extends Command
{
    protected $signature = 'billing:renew-subscriptions
        {--apply : Benar-benar menerbitkan invoice. Tanpa flag ini hanya melaporkan.}';

    protected $description = 'Terbitkan invoice perpanjangan (H-3) dan denda flat saat masa tenggang';

    public function handle(BillingRenewalService $service): int
    {
        // Command ini dijalankan `schedule:run` sebagai proses CONSOLE, sedangkan
        // DatabaseSettingsBridge hanya dipasang pada jalur web. Tanpa panggilan
        // ini, admin mematikan penagihan dari panel tetapi cron tetap jalan
        // memakai nilai .env — saklar di panel jadi tidak berpengaruh.
        app(DatabaseSettingsBridge::class)->apply();

        if (! config('billing.renewal_enabled', false)) {
            $this->warn('Penagihan otomatis dimatikan (billing.renewal_enabled = false). Tidak ada yang dikerjakan.');

            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');

        try {
            $stats = $service->renewDueSubscriptions($apply);
        } catch (\Throwable $e) {
            Log::error('billing:renew-subscriptions gagal', ['error' => $e->getMessage()]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if (! $apply) {
            $this->warn('MODE LAPORAN: tidak ada perubahan disimpan. Tambahkan --apply untuk mengeksekusi.');
            $this->table(['Yang AKAN terjadi', 'Jumlah'], [
                ['invoice akan diterbitkan', (string) $stats['would_create']],
                ['tautan lama akan di-expire', (string) $stats['would_expire']],
                ['dilewati (sudah ditagih)', (string) $stats['skipped']],
            ]);

            return self::SUCCESS;
        }

        $this->info('Penagihan perpanjangan selesai.');
        $this->table(['Metrik', 'Jumlah'], collect($stats)->map(fn ($n, $k) => [$k, (string) $n])->values()->all());

        return self::SUCCESS;
    }
}
