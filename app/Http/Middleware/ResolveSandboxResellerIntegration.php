<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ResellerIntegrationLookup;
use App\Support\ResellerApiResponse;
use Closure;
use Illuminate\Http\Request;

class ResolveSandboxResellerIntegration
{
    public function __construct(
        private readonly ResellerIntegrationLookup $lookup
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->attributes->get('api_user');

        if (! $user instanceof User) {
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

        $integration = $this->lookup->findOwnedActive($user, $integrationCode, 'sandbox');

        if (! $integration) {
            return ResellerApiResponse::error(
                'Invalid or inactive reseller integration code',
                ResellerApiResponse::INVALID_INTEGRATION_CODE,
                403,
            );
        }

        $request->attributes->set('sandbox_reseller_integration', $integration);

        return $next($request);
    }
}
