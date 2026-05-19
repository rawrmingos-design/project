<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $adminDomain = $this->normalizeHost((string) env('FILAMENT_ADMIN_DOMAIN', ''));
        $requestHost = $this->normalizeHost((string) $request->getHost());

        if (! Auth::check()) {
            if ($adminDomain !== '' && $requestHost !== '' && $requestHost === $adminDomain) {
                return redirect('/login');
            }

            return redirect()->route('login');
        }

        if ((string) Auth::user()->role === 'Admin') {
            return $next($request);
        }

        if ($adminDomain !== '' && $requestHost !== '' && $requestHost === $adminDomain) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login');
        }

        if ($request->routeIs('dashboard')) {
            abort(403);
        }

        return redirect()->route('dashboard');
    }

    private function normalizeHost(string $host): string
    {
        $normalized = trim(strtolower($host));

        if ($normalized === '') {
            return '';
        }

        if (str_contains($normalized, '://')) {
            $normalized = (string) (parse_url($normalized, PHP_URL_HOST) ?? '');
        }

        if ($normalized === '') {
            return '';
        }

        return (string) (preg_replace('/:\d+$/', '', $normalized) ?? $normalized);
    }
}
