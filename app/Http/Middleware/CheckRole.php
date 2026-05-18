<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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
        if (! Auth::check()) {
            $adminDomain = $this->normalizeHost((string) env('FILAMENT_ADMIN_DOMAIN', ''));
            $requestHost = $this->normalizeHost((string) $request->getHost());

            if ($adminDomain !== '' && $requestHost !== '' && strcasecmp($requestHost, $adminDomain) === 0) {
                if (Route::has('filament.admin.auth.login')) {
                    return redirect()->route('filament.admin.auth.login');
                }

                return redirect('/login');
            }

            return redirect()->route('login');
        }

        if ((string) Auth::user()->role === 'Admin') {
            return $next($request);
        }

        $adminDomain = $this->normalizeHost((string) env('FILAMENT_ADMIN_DOMAIN', ''));
        $requestHost = $this->normalizeHost((string) $request->getHost());

        if ($adminDomain !== '' && $requestHost !== '' && strcasecmp($requestHost, $adminDomain) === 0) {
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

    private function normalizeHost(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (str_contains($value, '://')) {
            $value = (string) (parse_url($value, PHP_URL_HOST) ?? '');
        }

        $value = preg_replace('/:\d+$/', '', $value) ?? '';

        return strtolower(trim($value));
    }
}
