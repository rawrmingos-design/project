<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);

        try {
            if ((bool) config('tenancy.disabled', true)) {
                $host = $this->normalizeHost($request->getHost());

                abort_if($host !== '' && ! $this->shouldBypassHost($host), 404);

                return $next($request);
            }

            $tenant = $this->resolveTenant($request);

            if ($tenant) {
                $context->set($tenant);
                app()->instance('tenant', $tenant);
                View::share('tenant', $tenant);
            }

            return $next($request);
        } finally {
            $context->clear();
            app()->forgetInstance('tenant');
            View::share('tenant', null);
        }
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        $host = $this->normalizeHost($request->getHost());

        if ($host === '' || $this->shouldBypassHost($host)) {
            return null;
        }

        // Check if any tenant has claimed this custom domain (regardless of verification status)
        $customDomainTenant = Tenant::query()
            ->where('custom_domain', $host)
            ->first();

        if ($customDomainTenant) {
            // A tenant has claimed this domain. Only resolve if fully verified and active.
            // If conditions are not met, return null — do NOT fall through to subdomain resolution.
            if (
                $customDomainTenant->status === Tenant::STATUS_ACTIVE
                && $customDomainTenant->custom_domain_status === Tenant::DOMAIN_STATUS_VERIFIED
                && $customDomainTenant->custom_domain_verified_at !== null
            ) {
                return $customDomainTenant;
            }

            return null;
        }

        // No tenant has this host as a custom domain — try subdomain resolution
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain === null) {
            return null;
        }

        return Tenant::query()
            ->where('subdomain', $subdomain)
            ->where('status', Tenant::STATUS_ACTIVE)
            ->first();
    }

    protected function shouldBypassHost(string $host): bool
    {
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return true;
        }

        return in_array($host, array_filter([
            $this->normalizeHost((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            $this->normalizeHost((string) env('FILAMENT_ADMIN_DOMAIN', '')),
            $this->normalizeHost((string) env('DOCS_DOMAIN', '')),
        ]), true);
    }

    protected function extractSubdomain(string $host): ?string
    {
        $baseHost = $this->normalizeHost((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        if ($baseHost === '' || $host === $baseHost || ! str_ends_with($host, '.' . $baseHost)) {
            return null;
        }

        $subdomain = substr($host, 0, -1 * (strlen($baseHost) + 1));

        if ($subdomain === '' || str_contains($subdomain, '.')) {
            return null;
        }

        return $subdomain;
    }

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
}
