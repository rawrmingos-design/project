<?php

namespace App\Tenancy;

use App\Models\Tenant;
use App\Tenancy\Contracts\CloudflareDnsClientInterface;
use Illuminate\Support\Facades\Log;

/**
 * Satu sumber kebenaran untuk pertanyaan "nama subdomain ini boleh dipakai?".
 *
 * Dipakai BERSAMA oleh endpoint cek ketersediaan dan jalur pendaftaran,
 * supaya keduanya tidak pernah berbeda jawaban.
 *
 * Urutan pemeriksaan:
 *   1. saklar guard (bisa dimatikan tanpa deploy)
 *   2. format setelah normalisasi
 *   3. daftar reserved (penopang utama — wildcard DNS membuat cek DNS mentah
 *      tidak bisa membedakan nama terpakai dari yang belum)
 *   4. sudah dipakai tenant lain di database
 *   5. record DNS eksplisit di Cloudflare (pertahanan berlapis, fail-open)
 */
class SubdomainAvailabilityGuard
{
    public function __construct(
        private readonly CloudflareDnsClientInterface $cloudflare,
    ) {}

    /**
     * @return array{available: bool, subdomain: string, reason: ?string}
     */
    public function check(string $raw, ?string $baseHost = null): array
    {
        $subdomain = $this->normalize($raw);

        if (! $this->isEnabled()) {
            return $this->result(true, $subdomain, null);
        }

        if (($error = $this->formatError($subdomain)) !== null) {
            return $this->result(false, $subdomain, $error);
        }

        if (($reason = SubdomainReservationPolicy::reasonFor($subdomain)) !== null) {
            return $this->result(false, $subdomain, $reason);
        }

        if (Tenant::query()->where('subdomain', $subdomain)->exists()) {
            return $this->result(false, $subdomain, 'Nama ini sudah dipakai. Silakan pilih nama lain.');
        }

        if (($reason = $this->dnsConflict($subdomain, $baseHost)) !== null) {
            return $this->result(false, $subdomain, $reason);
        }

        return $this->result(true, $subdomain, null);
    }

    public function isEnabled(): bool
    {
        return (bool) config('tenancy.subdomain_guard_enabled', true);
    }

    /** Normalisasi sama dengan yang dipakai pendaftaran tenant. */
    public function normalize(string $subdomain): string
    {
        $subdomain = strtolower(trim($subdomain));
        $subdomain = preg_replace('/[^a-z0-9-]/', '-', $subdomain) ?? '';
        $subdomain = preg_replace('/-+/', '-', $subdomain) ?? '';

        return trim($subdomain, '-');
    }

    /**
     * @return array{available: bool, subdomain: string, reason: ?string}
     */
    private function result(bool $available, string $subdomain, ?string $reason): array
    {
        return [
            'available' => $available,
            'subdomain' => $subdomain,
            'reason' => $reason,
        ];
    }

    private function formatError(string $subdomain): ?string
    {
        if ($subdomain === '') {
            return 'Nama subdomain wajib diisi.';
        }

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{1,61}[a-z0-9])$/', $subdomain)) {
            return 'Gunakan huruf, angka, dan strip. Minimal 3 karakter.';
        }

        return null;
    }

    /**
     * Pertahanan berlapis: deteksi nama yang punya record DNS eksplisit
     * walau tidak ada di daftar reserved.
     *
     * FAIL-OPEN: kalau Cloudflare tidak bisa dihubungi, jangan blokir
     * pendaftaran yang sah — cukup catat untuk ditinjau.
     */
    private function dnsConflict(string $subdomain, ?string $baseHost): ?string
    {
        $zoneId = (string) config('tenancy.cloudflare.zone_id', '');
        $base = $baseHost ?: (string) parse_url((string) config('app.url'), PHP_URL_HOST);

        if (blank($zoneId) || blank($base)) {
            return null;
        }

        $host = $subdomain . '.' . strtolower($base);

        try {
            $taken = $this->cloudflare->hostnamesUnder($zoneId, strtolower($base));
        } catch (\Throwable $e) {
            Log::warning('SubdomainAvailabilityGuard: cek Cloudflare gagal, diloloskan (fail-open).', [
                'subdomain' => $subdomain,
                'host' => $host,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        return in_array($host, $taken, true)
            ? 'Nama ini sudah dipakai. Silakan pilih nama lain.'
            : null;
    }
}
