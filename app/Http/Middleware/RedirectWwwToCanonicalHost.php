<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectWwwToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        $appUrl = trim((string) config('app.url'));
        $canonicalHost = $this->normalizeHost((string) parse_url($appUrl, PHP_URL_HOST));

        if ($canonicalHost === '') {
            return $next($request);
        }

        $requestHost = $this->normalizeHost($request->getHost());

        if ($requestHost !== 'www.' . $canonicalHost) {
            return $next($request);
        }

        $canonicalScheme = (string) (parse_url($appUrl, PHP_URL_SCHEME) ?: 'https');

        return redirect()->away(
            $canonicalScheme . '://' . $canonicalHost . $request->getRequestUri(),
            301,
        );
    }

    private function normalizeHost(string $host): string
    {
        return strtolower(preg_replace('/:\d+$/', '', trim($host)) ?? '');
    }
}
