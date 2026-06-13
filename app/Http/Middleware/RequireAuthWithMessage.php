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
            
            return redirect()
                ->route('login')
                ->with('warning', $message ?? $defaultMessage);
        }

        return $next($request);
    }
}
