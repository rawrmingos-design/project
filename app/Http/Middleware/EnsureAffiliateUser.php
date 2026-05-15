<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAffiliateUser
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

        if (! $isAffiliateActive) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Fitur redeem saldo hanya tersedia untuk akun affiliate yang sudah aktif.',
                    'error_code' => 'AFFILIATE_ONLY',
                ], 403);
            }

            return redirect()
                ->route('dashboard')
                ->with('error', 'Fitur redeem saldo hanya tersedia untuk akun affiliate yang sudah aktif.');
        }

        return $next($request);
    }
}
