<?php

namespace App\Http\Middleware;

use App\Tenancy\Concerns\ResolvesTenantHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * D13.2: halaman tagihan hanya hidup di host platform.
 *
 * Subdomain tenant TIDAK boleh menyajikan halaman ini: alamatnya bisa ditebak
 * (satu label + basis domain yang sama), dan halaman tagihan sendiri adalah
 * halaman platform — bukan halaman tenant. Di host lain dibuat 404 biasa supaya
 * keberadaannya pun tidak terkonfirmasi.
 */
class RequirePlatformHost
{
    use ResolvesTenantHost;

    public function handle(Request $request, Closure $next): Response
    {
        $host = $this->normalizeHost($request->getHost());
        $baseHost = $this->baseHost();

        // Tanpa basis domain yang bisa dibaca, jangan menebak: lebih baik
        // melayani (halaman ini tetap ber-token dan `noindex`) daripada
        // mematikan rute karena konfigurasi yang cacat.
        if ($baseHost === '') {
            return $next($request);
        }

        if (! in_array($host, $this->publicHosts(), true)) {
            abort(404);
        }

        return $next($request);
    }
}
