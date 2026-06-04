<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\ResellerApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

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

        $user = $this->resolveUserFromToken($bearerToken);

        if (!$user) {
            return ResellerApiResponse::error(
                'Invalid Token',
                ResellerApiResponse::INVALID_TOKEN,
                403,
            );
        }

        $request->attributes->set('api_user', $user);

        return $next($request);
    }

    /**
     * Resolve a User from a raw bearer token.
     *
     * Strategy:
     *   1. Use the first 8 chars as an indexed prefix to narrow candidates.
     *   2. Run Hash::check on each candidate (typically just 1).
     *   3. Backward-compat fallback for legacy users whose api_key is still
     *      stored as plain text (pre-rotation). Will be removed in Phase 3.
     */
    private function resolveUserFromToken(string $token): ?User
    {
        $prefix = substr($token, 0, 8);

        // ── Path A: prefix-based lookup (post-rotation, bcrypt keys) ──────────
        if ($prefix !== '') {
            $candidates = User::query()
                ->where('api_key_prefix', $prefix)
                ->get();

            foreach ($candidates as $candidate) {
                if ($candidate->verifyApiKey($token)) {
                    return $candidate;
                }
            }
        }

        // ── Path B: legacy plain-text key fallback (pre-rotation users) ───────
        // User yang belum rotate key mereka masih pakai plain-text di kolom api_key.
        // Controller tidak perlu duplikat ini — middleware yang handle.
        // Emit warning agar admin bisa monitor siapa yang perlu diingatkan untuk rotate key.
        $legacy = User::query()
            ->whereNull('api_key_prefix')
            ->where('api_key', $token)
            ->first();

        if ($legacy) {
            Log::warning('H2H API: Legacy plain-text key used — user should rotate their API key.', [
                'user_id'  => $legacy->id,
                'username' => $legacy->username,
                'ip'       => request()->ip(),
                'hint'     => 'Go to Reseller Hub → Credentials → Rotate Key',
            ]);

            return $legacy;
        }

        return null;
    }
}
