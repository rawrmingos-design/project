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

        // SHA-256 Lookup
        $hash = hash('sha256', $bearerToken);
        $integration = ResellerIntegration::where('api_key_hash', $hash)
            ->where('mode', 'live')
            ->where('is_active', true)
            ->with('user')
            ->first();

        if (! $integration || ! $integration->user) {
            return ResellerApiResponse::error(
                'Invalid Token',
                ResellerApiResponse::INVALID_TOKEN,
                403,
            );
        }

        // Token is valid, update last used at (optional, can be moved to a job if high load)
        $integration->updateQuietly(['api_key_last_used_at' => now()]);

        $request->attributes->set('api_user', $integration->user);
        $request->attributes->set('live_reseller_integration', $integration);

        return $next($request);
    }
}
