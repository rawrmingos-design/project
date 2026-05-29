<?php

namespace App\Http\Middleware;

use App\Services\SandboxApiKeyService;
use App\Support\ResellerApiResponse;
use Closure;
use Illuminate\Http\Request;

class AuthenticateSandboxApi
{
    public function __construct(
        private readonly SandboxApiKeyService $sandboxApiKeyService
    ) {
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $request->expectsJson()) {
            $request->headers->set('Accept', 'application/json');
        }

        $bearerToken = trim((string) $request->bearerToken());

        if ($bearerToken === '') {
            return ResellerApiResponse::error(
                'Access Token is required',
                ResellerApiResponse::ACCESS_TOKEN_REQUIRED,
                403,
            );
        }

        $user = $this->sandboxApiKeyService->resolveUserFromToken($bearerToken);

        if (! $user) {
            return ResellerApiResponse::error(
                'Invalid Token',
                ResellerApiResponse::INVALID_TOKEN,
                403,
            );
        }

        $user->forceFill([
            'sandbox_api_key_last_used_at' => now(),
        ])->save();

        $request->attributes->set('api_user', $user);

        return $next($request);
    }
}
