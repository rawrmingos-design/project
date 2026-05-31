<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsReseller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->resellerIntegrations()->exists()) {
            return redirect()->route('dashboard')->with('error', 'Akses ditolak: Anda tidak memiliki integrasi Reseller.');
        }

        return $next($request);
    }
}
