<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureNonAffiliateUser
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $isAffiliateActive = method_exists($user, 'isAffiliateActive')
            ? (bool) $user->isAffiliateActive()
            : strtolower(trim((string) ($user->affiliate_status ?? ''))) === 'active';

        if ($isAffiliateActive) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun affiliate tidak dapat melakukan top up saldo.',
                    'error_code' => 'AFFILIATE_DEPOSIT_FORBIDDEN',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Akun affiliate tidak dapat melakukan top up saldo.');
        }

        return $next($request);
    }
}
