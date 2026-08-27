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
        $adminDomain = $this->normalizeHost((string) config('app.filament_admin_domain', 'adminpanel.istanatopup.com'));
        $requestHost = $this->normalizeHost((string) $request->getHost());

        if ($adminDomain !== '' && $requestHost !== '' && $requestHost === $adminDomain) {
            return $next($request);
        }
        // Jalankan sistem maintenance normal Laravel untuk domain lainnya
        return parent::handle($request, $next);
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
