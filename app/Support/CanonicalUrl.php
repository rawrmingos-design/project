<?php

namespace App\Support;

class CanonicalUrl
{
    public static function normalize(?string $url = null): string
    {
        $candidate = trim((string) $url);

        if ($candidate === '') {
            $candidate = request()->getRequestUri() ?: '/';
        }

        $parts = parse_url($candidate);

        if ($parts === false) {
            $parts = [];
        }

        $host = strtolower((string) ($parts['host'] ?? request()->getHost()));
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $path = (string) ($parts['path'] ?? '');
        if ($path === '') {
            $path = '/';
        }

        if (! str_starts_with($path, '/')) {
            $path = '/' . $path;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $query = '';
        parse_str((string) ($parts['query'] ?? ''), $queryParams);
        $page = filter_var($queryParams['page'] ?? null, FILTER_VALIDATE_INT);
        if ($page !== false && $page !== null && $page > 1) {
            $query = '?page=' . $page;
        }

        return 'https://' . $host . $port . $path . $query;
    }
}
