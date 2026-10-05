<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireAuthWithMessage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $message
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $message = null)
    {
        if (!auth()->check()) {
            $defaultMessage = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';

            // Bawa tujuan asli lewat `?redirect=` supaya setelah login user kembali
            // ke halaman yang dia tuju, bukan mendarat di dashboard.
            //
            // Kenapa `?redirect=` dan bukan `url.intended`: aplikasi ini TIDAK memakai
            // `url.intended` di jalur login web — `LoginController` memakai trait
            // `HandlesLoginRedirect` yang membaca `?redirect=` lalu memvalidasinya
            // (hanya path relatif, tolak host/skema => aman dari open redirect).
            // Sengaja tidak memakai `redirect()->guest()` juga supaya `url.intended`
            // tidak terisi URL docs dan bocor ke `redirect()->intended()` milik login
            // Filament admin.
            //
            // `getRequestUri()` hanya berisi path + query (tanpa host), dan setelah
            // login `redirect()->to($target)` di-resolve terhadap host request — jadi
            // user docs kembali ke host docs, bukan dilempar ke host utama.
            $params = [];

            if (($target = $this->intendedPath($request)) !== null) {
                $params['redirect'] = $target;
            }

            return redirect()
                ->route('login', $params)
                ->with('warning', $message ?? $defaultMessage);
        }

        return $next($request);
    }

    /**
     * Path + query request saat ini (tanpa host) — bentuk yang diterima
     * `HandlesLoginRedirect::safeLoginRedirect()`. Null kalau tidak layak dipakai.
     */
    private function intendedPath(Request $request): ?string
    {
        $target = $request->getRequestUri();

        if (! is_string($target) || $target === '') {
            return null;
        }

        // Harus path absolut-situs, tapi bukan protocol-relative (`//host`).
        if (! str_starts_with($target, '/') || str_starts_with($target, '//')) {
            return null;
        }

        return $target;
    }
}
