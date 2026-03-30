<?php

namespace App\Services;

use App\Http\Controllers\DigiFlazzController;
use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\VipResellerController;
use App\Models\Provider;
use RuntimeException;

class ProviderBalanceService
{
    /**
     * @return array{success:bool,balance:float|null,message:string}
     */
    public function sync(Provider $provider): array
    {
        $config = $this->resolveConfig($provider);
        $rawBalance = null;

        switch ($provider->code) {
            case 'digiflazz':
                $res = (new DigiFlazzController($config))->cekSaldo();
                $rawBalance = $res['data']['deposit'] ?? null;
                break;

            case 'bangjeff':
                $res = (new BangJeffController($config))->balance();
                $rawBalance = $res['data']['balance'] ?? null;
                break;

            case 'vip':
            case 'vip_reseller':
                $res = (new VipResellerController($config))->profile();

                if (($res['result'] ?? true) === false) {
                    throw new RuntimeException(
                        $res['message'] ?? 'VIP Reseller gagal mengembalikan data profile.'
                    );
                }

                $rawBalance = $res['data']['balance']
                    ?? $res['data']['saldo']
                    ?? $res['data']['sisa_saldo']
                    ?? $res['data']['profile']['balance']
                    ?? $res['data']['profile']['saldo']
                    ?? $res['balance']
                    ?? $res['saldo']
                    ?? null;
                break;

            case 'apigames':
                throw new RuntimeException('ApiGames balance check belum diimplementasikan.');

            default:
                throw new RuntimeException('Provider tidak didukung untuk check balance: ' . $provider->code);
        }

        $normalizedBalance = $this->normalizeBalanceValue($rawBalance);

        if ($normalizedBalance === null) {
            throw new RuntimeException(
                'Format saldo provider tidak valid: ' . (is_scalar($rawBalance) ? (string) $rawBalance : json_encode($rawBalance))
            );
        }

        $provider->update([
            'balance' => $normalizedBalance,
            'last_check_at' => now(),
        ]);

        return [
            'success' => true,
            'balance' => $normalizedBalance,
            'message' => 'Saldo provider berhasil diperbarui.',
        ];
    }

    private function resolveConfig(Provider $provider): array
    {
        $config = [];

        if (! empty($provider->api_username)) {
            $config['username'] = $provider->api_username;
            $config['api_id'] = $provider->api_username;
        }

        if (! empty($provider->api_key)) {
            $config['api_key'] = $provider->api_key;
        }

        if (! empty($provider->api_endpoint)) {
            $config['endpoint'] = $provider->api_endpoint;
        }

        return $config;
    }

    private function normalizeBalanceValue(mixed $rawBalance): ?float
    {
        if (is_int($rawBalance) || is_float($rawBalance)) {
            return (float) $rawBalance;
        }

        if (! is_string($rawBalance)) {
            return null;
        }

        $value = trim($rawBalance);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';

        if ($value === '' || $value === '-' || $value === '.' || $value === ',') {
            return null;
        }

        if (preg_match('/^\-?\d{1,3}([.,]\d{3})+$/', $value) === 1) {
            $value = str_replace([',', '.'], '', $value);
        } elseif (str_contains($value, ',') && str_contains($value, '.')) {
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');

            if ($lastComma !== false && $lastDot !== false) {
                if ($lastComma > $lastDot) {
                    $value = str_replace('.', '', $value);
                    $value = str_replace(',', '.', $value);
                } else {
                    $value = str_replace(',', '', $value);
                }
            }
        } elseif (str_contains($value, ',')) {
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}

