<?php

namespace App\Console\Commands;

use App\Services\Providers\BangJeffService;
use Illuminate\Console\Command;

class BangJeffDiagnostics extends Command
{
    protected $signature = 'bangjeff:diagnose
        {--endpoint= : Override BangJeff base endpoint}
        {--region= : Override region, defaults to config/admin setting}
        {--products : Also fetch product list}
        {--raw : Print raw decoded responses after the summary}';

    protected $description = 'Run safe BangJeff balance/product diagnostics using the application runtime credentials.';

    public function handle(): int
    {
        $config = array_filter([
            'endpoint' => trim((string) $this->option('endpoint')),
            'region' => trim((string) $this->option('region')),
        ], fn ($value): bool => $value !== '');

        $service = new BangJeffService($config);
        $balanceResponse = $service->balance();
        $balanceOk = ($balanceResponse['rc'] ?? null) === '00';
        $balance = $balanceResponse['data']['balance']['value']
            ?? $balanceResponse['data']['balance']
            ?? null;

        $rows = [
            ['Balance RC', (string) ($balanceResponse['rc'] ?? '-')],
            ['Balance Message', (string) ($balanceResponse['message'] ?? '-')],
            ['Balance', $balance !== null ? 'Rp ' . number_format((float) $balance, 0, ',', '.') : '-'],
        ];

        $productResponse = null;
        $productOk = true;

        if ($this->option('products')) {
            $productResponse = $service->getProductsRaw();
            $productOk = ($productResponse['rc'] ?? null) === '00';
            $products = is_array($productResponse['data'] ?? null) ? $productResponse['data'] : [];

            $rows[] = ['Products RC', (string) ($productResponse['rc'] ?? '-')];
            $rows[] = ['Products Message', (string) ($productResponse['message'] ?? '-')];
            $rows[] = ['Products Count', (string) count($products)];
        }

        $this->table(['Check', 'Result'], $rows);

        if ($this->option('raw')) {
            $this->newLine();
            $this->line('Balance raw response:');
            $this->line(json_encode($balanceResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            if ($productResponse !== null) {
                $this->newLine();
                $this->line('Products raw response:');
                $this->line(json_encode($productResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }
        }

        if (! $balanceOk) {
            $this->error('BangJeff balance check failed. Check admin API key, endpoint, region, and config cache.');
        }

        if (! $productOk) {
            $this->error('BangJeff product check failed. Check admin API key, endpoint, region, and config cache.');
        }

        return $balanceOk && $productOk ? self::SUCCESS : self::FAILURE;
    }
}
