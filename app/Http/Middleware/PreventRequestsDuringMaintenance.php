<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class PreventRequestsDuringMaintenance extends Middleware
{
    /**
     * The URIs that should be reachable while maintenance mode is enabled.
     *
     * @var array<int, string>
     */
    protected $except = [
        'web/up',
        'web/mt'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function handle($request, Closure $next)
    {
        // Pengecualian otomatis untuk domain/subdomain admin
        $adminDomain = env('FILAMENT_ADMIN_DOMAIN', 'adminpanel.istanatopup.com');
        
        if ($request->getHost() === $adminDomain) {
            return $next($request);
        }
        // Jalankan sistem maintenance normal Laravel untuk domain lainnya
        return parent::handle($request, $next);
    }
}
