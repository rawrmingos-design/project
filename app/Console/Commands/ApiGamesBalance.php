<?php

namespace App\Console\Commands;

use App\Services\Providers\ApiGamesService;
use Illuminate\Console\Command;

class ApiGamesBalance extends Command
{
    protected $signature = 'apigames:balance
        {--merchant= : Override merchant ID instead of reading from settings}
        {--secret= : Override secret key instead of reading from settings}
        {--endpoint= : Override base endpoint, defaults to https://v1.apigames.id/v2}
        {--raw : Print the raw decoded response after the summary}';

    protected $description = 'Check ApiGames account info and current saldo from the application runtime.';

    public function handle(): int
    {
        $config = array_filter([
            'merchant_id' => trim((string) $this->option('merchant')),
            'secret_key' => trim((string) $this->option('secret')),
            'endpoint' => trim((string) $this->option('endpoint')),
        ], fn ($value): bool => $value !== '');

        $service = new ApiGamesService($config);
        $response = $service->balance();

        if (! ($response['result'] ?? false)) {
            $this->error('ApiGames balance check failed: ' . ($response['message'] ?? 'Unknown error.'));

            if ($this->option('raw')) {
                $this->newLine();
                $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            return self::FAILURE;
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $saldo = $response['balance'] ?? $data['saldo'] ?? null;

        $this->table(
            ['Field', 'Value'],
            [
                ['Merchant ID', (string) ($data['merchant_id'] ?? '-')],
                ['Name', (string) ($data['nama'] ?? '-')],
                ['Saldo', $saldo !== null ? 'Rp ' . number_format((float) $saldo, 0, ',', '.') : '-'],
            ]
        );

        if ($this->option('raw')) {
            $this->newLine();
            $this->line(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
