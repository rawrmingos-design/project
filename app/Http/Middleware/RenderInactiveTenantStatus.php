<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Public\TenantStatusPageController;
use App\Models\Tenant;
use App\Tenancy\Concerns\ResolvesTenantHost;
use App\Tenancy\TenantAddressMasker;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menghapus 404 polos untuk tenant non-aktif.
 *
 * Dipasang SEBELUM `tenant.required` di grup rute tenant. Kalau host-nya milik
 * tenant `suspended` atau `pending_payment`, middleware ini merender halaman
 * status Inertia dan menghentikan request. Selain itu request diteruskan apa
 * adanya — `active` dilayani jalur normal, `cancelled`/host tak dikenal tetap
 * jatuh ke `tenant.required` → 404.
 *
 * Catatan: karena halaman ini tidak butuh TenantContext, tidak boleh ada
 * `TenantContext::set()` di sini. Menyetel konteks membuat global scope tenant
 * mengosongkan halaman ini sendiri.
 */
class RenderInactiveTenantStatus
{
    use ResolvesTenantHost;

    public function handle(Request $request, Closure $next): Response|InertiaResponse
    {
        if ((bool) config('tenancy.disabled', true)) {
            return $next($request);
        }

        // Halaman status hanya menggantikan HALAMAN (GET/HEAD). Menulis ulang
        // POST/PUT/DELETE akan mengubah arti request (mis. forgot-password di
        // subdomain tenant non-aktif) tanpa alasan.
        if (! $request->isMethodSafe()) {
            return $next($request);
        }

        $host = $this->normalizeHost($request->getHost());

        if ($host === '' || $this->shouldBypassHost($host)) {
            return $next($request);
        }

        $tenant = $this->findInactiveTenant($host);

        if ($tenant === null) {
            return $next($request);
        }

        $request->attributes->set('tenant_inactive_status', (string) $tenant->status);
        $request->attributes->set('tenant_inactive_id', $tenant->getKey());
        $request->attributes->set(
            'tenant_inactive_address',
            app(TenantAddressMasker::class)->maskHost($host, $this->baseHost())
        );

        // Halaman status adalah halaman PLATFORM: tidak memakai
        // Navbar/Footer/storefront, dan identitas tenant tidak boleh ikut
        // terkirim. Shared props storefront (`siteConfig`, `seoDefaults`)
        // memuat host tenant mentah (canonical, URL aset), jadi buang dulu —
        // props halaman ini dibangun sendiri oleh controller.
        Inertia::flushShared();

        return app(TenantStatusPageController::class)($request);
    }

    /**
     * D11: hanya `suspended` + `pending_payment`. `cancelled` sengaja tidak
     * dapat halaman penjelasan karena namanya sudah dilepas.
     */
    protected function findInactiveTenant(string $host): ?Tenant
    {
        $query = Tenant::query()->whereIn('status', [
            Tenant::STATUS_SUSPENDED,
            Tenant::STATUS_PENDING_PAYMENT,
        ]);

        $customDomainTenant = (clone $query)->where('custom_domain', $host)->first();

        if ($customDomainTenant !== null) {
            return $customDomainTenant;
        }

        $subdomain = $this->extractSubdomain($host);

        if ($subdomain === null) {
            return null;
        }

        return (clone $query)->where('subdomain', $subdomain)->first();
    }
}
