<?php

namespace App\Http\Middleware;

use App\Models\ResellerIntegration;
use App\Services\ResellerIntegrationLookup;
use App\Models\User;
use App\Support\ResellerApiResponse;
use Closure;
use Illuminate\Http\Request;

class ResolveLiveResellerIntegration
{
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = trim((string) $request->bearerToken());

        if ($bearerToken === '') {
            return ResellerApiResponse::error(
                'Access Token is required',
                ResellerApiResponse::ACCESS_TOKEN_REQUIRED,
                403,
            );
        }

        // Resolve via Cache Service
        $cacheService = app(\App\Services\ResellerIntegrationCacheService::class);
        $integration = $cacheService->resolveByHash(hash('sha256', $bearerToken), 'live');

        if (! $integration || ! $integration->user) {
            return ResellerApiResponse::error(
                'Invalid Token',
                ResellerApiResponse::INVALID_TOKEN,
                403,
            );
        }

        // Token is valid, defer last used update to after response (throttled)
        $cacheService->touchLastUsed($integration);

        $request->attributes->set('api_user', $integration->user);
        $request->attributes->set('live_reseller_integration', $integration);

        return $next($request);
    }
}
