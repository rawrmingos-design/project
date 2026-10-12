<?php

namespace App\Tenancy\Concerns;

/**
 * Perhitungan host bersama untuk resolusi tenant.
 *
 * Diekstrak dari ResolveTenant supaya middleware halaman status tidak punya
 * definisi kedua yang bisa menyimpang dari jalur utama.
 */
trait ResolvesTenantHost
{
    protected function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));

        if ($host === '') {
            return '';
        }

        if (str_contains($host, '://')) {
            $host = (string) (parse_url($host, PHP_URL_HOST) ?? '');
        }

        return preg_replace('/:\d+$/', '', $host) ?? '';
    }

    /** Basis domain platform, mis. `topupengine.test`. */
    protected function baseHost(): string
    {
        return $this->normalizeHost((string) parse_url((string) config('app.url'), PHP_URL_HOST));
    }

    protected function publicHosts(): array
    {
        $publicHost = $this->baseHost();

        if ($publicHost === '') {
            return [];
        }

        return [$publicHost, 'www.' . $publicHost];
    }

    protected function shouldBypassHost(string $host): bool
    {
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        return in_array($host, array_filter([
            ...$this->publicHosts(),
            $this->normalizeHost((string) config('app.filament_admin_domain', '')),
            $this->normalizeHost((string) config('app.docs_domain', '')),
        ]), true);
    }

    protected function extractSubdomain(string $host): ?string
    {
        $baseHost = $this->baseHost();

        if ($baseHost === '' || $host === $baseHost || ! str_ends_with($host, '.' . $baseHost)) {
            return null;
        }

        $subdomain = substr($host, 0, -1 * (strlen($baseHost) + 1));

        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        return $subdomain;
    }
}
