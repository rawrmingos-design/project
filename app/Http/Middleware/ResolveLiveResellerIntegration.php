<?php

namespace App\Http\Middleware;

use App\Models\ResellerIntegration;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ResolveLiveResellerIntegration
{
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
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

        $integrationCode = trim((string) $request->header('X-Reseller-Integration-Code'));

        if ($integrationCode === '') {
            return response()->json([
                'error' => true,
                'code' => 422,
                'message' => 'X-Reseller-Integration-Code header is required',
            ], 422);
        }

        $integration = ResellerIntegration::query()
            ->where('integration_code', $integrationCode)
            ->where('user_id', $user->getKey())
            ->where('mode', 'live')
            ->where('is_active', true)
            ->first();

        if (! $integration) {
            return response()->json([
                'error' => true,
                'code' => 403,
                'message' => 'Invalid or inactive reseller integration code',
            ], 403);
        }

        $request->attributes->set('api_user', $user);
        $request->attributes->set('live_reseller_integration', $integration);

        return $next($request);
    }
}
