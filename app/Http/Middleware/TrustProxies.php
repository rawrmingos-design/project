<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers;

    public function __construct()
    {
        $this->proxies = $this->resolveTrustedProxies();
        $this->headers = $this->resolveTrustedHeaders();
    }

    /**
     * Resolve trusted proxies from config. Keep rollout conservative by
     * requiring explicit proxy configuration outside local/testing.
     *
     * @return array<int, string>|string|null
     */
    protected function resolveTrustedProxies(): array|string|null
    {
        $configured = config('trustedproxy.proxies');

        if ($configured === '*') {
            return '*';
        }

        if (is_array($configured)) {
            $proxies = array_values(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                $configured,
            )));

            return $proxies === [] ? null : $proxies;
        }

        $value = trim((string) $configured);

        if ($value === '') {
            return null;
        }

        if (str_contains($value, ',')) {
            $proxies = array_values(array_filter(array_map('trim', explode(',', $value))));

            return $proxies === [] ? null : $proxies;
        }

        return $value;
    }

    protected function resolveTrustedHeaders(): int
    {
        $configured = config('trustedproxy.headers');

        if (is_int($configured) && $configured > 0) {
            return $configured;
        }

        $tokens = is_array($configured)
            ? $configured
            : explode(',', (string) $configured);

        $map = [
            'forwarded_for' => Request::HEADER_X_FORWARDED_FOR,
            'forwarded_host' => Request::HEADER_X_FORWARDED_HOST,
            'forwarded_port' => Request::HEADER_X_FORWARDED_PORT,
            'forwarded_proto' => Request::HEADER_X_FORWARDED_PROTO,
            'forwarded_prefix' => Request::HEADER_X_FORWARDED_PREFIX,
            'aws_elb' => Request::HEADER_X_FORWARDED_AWS_ELB,
        ];

        $headers = 0;

        foreach ($tokens as $token) {
            $normalized = strtolower(trim((string) $token));

            if ($normalized !== '' && isset($map[$normalized])) {
                $headers |= $map[$normalized];
            }
        }

        if ($headers === 0) {
            return Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO |
                Request::HEADER_X_FORWARDED_AWS_ELB;
        }

        return $headers;
    }
}
