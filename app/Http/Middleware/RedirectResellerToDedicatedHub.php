<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect reseller users away from the regular user dashboard.
 *
 * Resellers have a dedicated hub at /id/reseller. Letting them access
 * /id/dashboard would show the wrong UI (regular user view with no H2H context).
 * This middleware enforces a clean separation of the two experiences.
 */
class RedirectResellerToDedicatedHub
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->resellerIntegrations()->exists()) {
            return redirect()->route('reseller.dashboard');
        }

        return $next($request);
    }
}
