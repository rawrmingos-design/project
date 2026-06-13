<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block reseller users from regular user panel routes.
 *
 * Accepts an optional route name parameter to specify the redirect destination.
 * Defaults to 'reseller.dashboard'. When redirecting to a functionally equivalent
 * page (e.g. reseller.deposits), no flash message is shown — the redirect is
 * seamless. Otherwise a flash info message is shown to explain the redirect.
 *
 * Usage:
 *   ->middleware('not-reseller')                        // → /id/reseller + flash
 *   ->middleware('not-reseller:reseller.deposits')      // → /id/reseller/deposits (no flash)
 */
class EnsureNotReseller
{
    public function handle(Request $request, Closure $next, string $redirectTo = 'reseller.dashboard'): Response
    {
        $user = $request->user();

        // Block Gold and Platinum users (active resellers)
        if ($user && in_array($user->role, ['Gold', 'Platinum'], true)) {
            $redirect = redirect()->route($redirectTo);

            // Only show a flash message when redirecting to the generic hub,
            // not when redirecting to a functionally equivalent reseller page.
            if ($redirectTo === 'reseller.dashboard') {
                $redirect->with('info', 'Halaman ini tidak tersedia untuk akun Reseller Hub.');
            }

            return $redirect;
        }

        return $next($request);
    }
}
