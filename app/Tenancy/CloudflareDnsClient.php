<?php

namespace App\Tenancy;

use App\Tenancy\Contracts\CloudflareDnsClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Klien Cloudflare: membaca record DNS zona untuk mendeteksi nama yang
 * sudah dipesan di luar daftar reserved.
 *
 * Hanya MEMBACA (`GET /dns_records`). Tidak pernah menulis.
 * Hasil di-cache supaya form pendaftaran tidak menghajar API Cloudflare.
 */
class CloudflareDnsClient implements CloudflareDnsClientInterface
{
    private const CACHE_PREFIX = 'cloudflare:dns-hostnames:';

    public function __construct(
        private readonly ?string $apiToken,
        private readonly int $cacheMinutes = 10,
    ) {}

    public function hostnamesUnder(string $zoneId, string $suffix): array
    {
        if (blank($this->apiToken) || blank($zoneId)) {
            throw new RuntimeException('Kredensial Cloudflare belum dikonfigurasi.');
        }

        $suffix = strtolower(rtrim($suffix, '.'));
        $key = self::CACHE_PREFIX . $zoneId . ':' . $suffix;

        $hostnames = Cache::remember(
            $key,
            now()->addMinutes($this->cacheMinutes),
            fn (): array => $this->fetch($zoneId, $suffix),
        );

        return is_array($hostnames) ? $hostnames : [];
    }

    /** @return array<string> */
    private function fetch(string $zoneId, string $suffix): array
    {
        $names = [];
        $page = 1;

        do {
            $response = Http::withToken($this->apiToken)
                ->timeout(10)
                ->acceptJson()
                ->get("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records", [
                    'per_page' => 100,
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException(
                    'Cloudflare API gagal (HTTP ' . $response->status() . ').'
                );
            }

            $payload = $response->json();
            $records = $payload['result'] ?? [];

            foreach ($records as $record) {
                $name = strtolower((string) ($record['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                // Wildcard BUKAN "nama terpakai" — dia menjawab semua nama.
                if (str_contains($name, '*')) {
                    continue;
                }

                // Fokus ke record di bawah suffix (calon nama tenant) + apex-nya.
                if ($name === $suffix || str_ends_with($name, '.' . $suffix)) {
                    $names[] = $name;
                }
            }

            $totalPages = (int) ($payload['result_info']['total_pages'] ?? 1);
            $page++;
        } while ($page <= $totalPages && $page <= 20);

        return array_values(array_unique($names));
    }
}
