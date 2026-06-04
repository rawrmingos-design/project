<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inject X-API-Version header into every /api/v1/* response.
 *
 * Applied only to the v1 route group in routes/api.php — NOT to webhook
 * routes, to avoid contaminating provider callback validation flows.
 *
 * Versioning strategy:
 *   X-API-Version: 1           → live endpoints (/api/v1/*)
 *   X-API-Version: 1-sandbox   → sandbox endpoints (/api/v1/sandbox/*)
 *
 * This is the foundation for future breaking changes (v2). Reseller
 * integrations can read this header to handle version-specific behavior.
 */
class AddApiVersionHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isSandbox = str_starts_with($request->path(), 'api/v1/sandbox');
        $version   = $isSandbox ? '1-sandbox' : '1';

        $response->headers->set('X-API-Version', $version);

        return $response;
    }
}
