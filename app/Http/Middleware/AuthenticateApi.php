<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ResellerApiResponse;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApi
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->expectsJson()) {
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

        $user = User::query()->where('api_key', $bearerToken)->first();

        if (! $user) {
            return ResellerApiResponse::error(
                'Invalid Token',
                ResellerApiResponse::INVALID_TOKEN,
                403,
            );
        }

        $request->attributes->set('api_user', $user);

        return $next($request);
    }
}
