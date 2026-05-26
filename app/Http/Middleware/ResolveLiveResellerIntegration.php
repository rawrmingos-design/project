<?php

namespace App\Http\Middleware;

use App\Models\ResellerIntegration;
use App\Models\User;
use App\Support\ResellerApiResponse;
use Closure;
use Illuminate\Http\Request;

class ResolveLiveResellerIntegration
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->attributes->get('api_user');

        if (! $user instanceof User) {
            $bearerToken = trim((string) $request->bearerToken());

            if ($bearerToken === '') {
                return ResellerApiResponse::error(
                    'Access Token is required',
                    ResellerApiResponse::ACCESS_TOKEN_REQUIRED,
                    403,
                );
            }

            $user = User::query()->where('api_key', $bearerToken)->first();
        }

        if (! $user) {
            return ResellerApiResponse::error(
                'Invalid Token',
                ResellerApiResponse::INVALID_TOKEN,
                403,
            );
        }

        $integrationCode = trim((string) $request->header('X-Reseller-Integration-Code'));

        if ($integrationCode === '') {
            return ResellerApiResponse::error(
                'X-Reseller-Integration-Code header is required',
                ResellerApiResponse::INTEGRATION_CODE_REQUIRED,
                422,
            );
        }

        $integration = ResellerIntegration::query()
            ->where('integration_code', $integrationCode)
            ->where('user_id', $user->getKey())
            ->where('mode', 'live')
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return ResellerApiResponse::error(
                'Invalid or inactive reseller integration code',
                ResellerApiResponse::INVALID_INTEGRATION_CODE,
                403,
            );
        }

        $request->attributes->set('api_user', $user);
        $request->attributes->set('live_reseller_integration', $integration);

        return $next($request);
    }
}
