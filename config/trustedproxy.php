<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | For inbound callback IP whitelisting to be reliable, only trusted reverse
    | proxies should be allowed to influence request()->ip() via forwarded
    | headers. Local/testing can trust all proxies for convenience, while
    | production should set TRUSTED_PROXIES explicitly.
    |
    */
    'proxies' => env(
        'TRUSTED_PROXIES',
        in_array(env('APP_ENV'), ['local', 'testing'], true) ? '*' : null,
    ),

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxy Headers
    |--------------------------------------------------------------------------
    |
    | Comma-separated tokens mapped in TrustProxies middleware. Keep the
    | default broad enough for common reverse proxy stacks, but only when the
    | proxy itself is trusted.
    |
    */
    'headers' => env(
        'TRUSTED_PROXY_HEADERS',
        'forwarded_for,forwarded_host,forwarded_port,forwarded_proto,aws_elb',
    ),
];

