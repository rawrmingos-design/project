<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\Concerns\ResolvesTenantHost;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    use ResolvesTenantHost;

    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);

        try {
            if ((bool) config('tenancy.disabled', true)) {
                $host = $this->normalizeHost($request->getHost());

                abort_if($this->isTenantHost($host), 404);

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

    protected function isTenantHost(string $host): bool
    {
        if ($host === '' || $this->shouldBypassHost($host)) {
            return false;
        }

        return Tenant::query()->where('custom_domain', $host)->exists()
            || $this->extractSubdomain($host) !== null;
    }
}
