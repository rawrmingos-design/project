<?php

namespace App\Services\Gateway;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Services\CheckId\CheckIdResolver;
use Illuminate\Validation\ValidationException;

class GatewayCheckIdService
{
    public function __construct(private readonly CheckIdResolver $resolver)
    {
    }

    public function check(array $payload): array
    {
        $categoryCode = strtolower(trim((string) ($payload['category_code'] ?? '')));
        if ($categoryCode === '') {
            throw ValidationException::withMessages([
                'category_code' => 'Kode produk wajib diisi.',
            ]);
        }

        $category = Kategori::query()
            ->where('kode', $categoryCode)
            ->first();

        if (! $category) {
            return [
                'ok' => false,
                'error_code' => 'CATEGORY_NOT_FOUND',
                'message' => 'Produk tidak ditemukan.',
                'data' => null,
            ];
        }

        $service = $this->resolveService($category, $payload);
        $uid = trim((string) ($payload['uid'] ?? ''));
        if ($uid === '') {
            throw ValidationException::withMessages([
                'uid' => 'User ID wajib diisi.',
            ]);
        }

        $result = $this->resolver->resolveForCategory(
            $category,
            $uid,
            isset($payload['zone']) ? (string) $payload['zone'] : null,
            $service,
        );

        $skipCheck = ($result['skip_check'] ?? false) === true;
        $statusCode = (int) ($result['status']['code'] ?? 0);
        $nickname = (string) ($result['data']['username'] ?? '');
        $valid = $skipCheck ? null : ($statusCode === 200 && $nickname !== '');
        $unavailable = ($result['unavailable'] ?? false) === true;

        return [
            'ok' => $skipCheck || $valid,
            'error_code' => $skipCheck || $valid ? null : ($unavailable ? 'CHECK_ID_UNAVAILABLE' : 'CHECK_ID_FAILED'),
            'message' => $skipCheck
                ? 'Produk ini tidak membutuhkan cek ID.'
                : ($valid
                    ? 'User ID valid.'
                    : ($unavailable
                        ? 'Validasi ID sedang tidak tersedia. Coba lagi beberapa saat.'
                        : 'User ID tidak ditemukan atau tidak valid.')),
            'data' => [
                'skip_check' => $skipCheck,
                'valid' => $valid,
                'uid' => $uid,
                'zone' => isset($payload['zone']) ? (string) $payload['zone'] : null,
                'nickname' => $nickname !== '' ? $nickname : null,
                'raw' => $result,
            ],
        ];
    }

    private function resolveService(Kategori $category, array $payload): ?Layanan
    {
        $serviceId = (int) ($payload['service_id'] ?? $payload['service'] ?? 0);
        if ($serviceId <= 0) {
            return null;
        }

        $service = Layanan::query()->find($serviceId);
        if (! $service) {
            throw ValidationException::withMessages([
                'service_id' => 'Layanan tidak ditemukan.',
            ]);
        }

        if ((int) $service->kategori_id !== (int) $category->id) {
            throw ValidationException::withMessages([
                'service_id' => 'Layanan tidak sesuai dengan produk.',
            ]);
        }

        return $service;
    }
}
