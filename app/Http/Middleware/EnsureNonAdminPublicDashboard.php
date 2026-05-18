<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class EnsureNonAdminPublicDashboard
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ((string) ($user->role ?? '') !== 'Admin') {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun admin tidak dapat mengakses dashboard pengguna.',
                'error_code' => 'ADMIN_PUBLIC_DASHBOARD_FORBIDDEN',
            ], 403);
        }

        if (Route::has('filament.admin.pages.dashboard')) {
            return redirect()->route('filament.admin.pages.dashboard');
        }

        return redirect('/dashboard');
    }
}
