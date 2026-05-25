<?php

namespace App\Http\Middleware;

use App\Models\User;
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
            return response()->json([
                'error' => true,
                'code' => 403,
                'message' => 'Access Token is required',
            ], 403);
        }

        $user = User::query()->where('api_key', $bearerToken)->first();

        if (! $user) {
            return response()->json([
                'error' => true,
                'code' => 403,
                'message' => 'Invalid Token',
            ], 403);
        }

        $request->attributes->set('api_user', $user);

        return $next($request);
    }
}
