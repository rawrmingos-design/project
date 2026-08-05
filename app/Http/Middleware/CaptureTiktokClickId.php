<?php

namespace App\Http\Middleware;

use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureTiktokClickId
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app(TenantContext::class)->has()) {
            return $next($request);
        }

        $ttclid = trim((string) $request->query('ttclid', ''));

        if ($ttclid !== '' && strlen($ttclid) <= 255 && preg_match('/^[A-Za-z0-9._~-]+$/', $ttclid) === 1) {
            cookie()->queue(cookie(
                'ttclid',
                $ttclid,
                60 * 24 * 28,
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                'lax',
            ));
        }

        return $next($request);
    }
}
