<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Prevent authenticated users from opening guest-only auth pages.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            return redirect('/id/dashboard');
        }

        return $next($request);
    }
}
