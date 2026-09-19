<?php

return [
    /*
     | Hosts allowed as remote QR-image sources for the invoice QR proxy route.
     | The proxy fetches these hosts server-side and streams the response back to
     | the browser, so every entry is an SSRF trust decision — keep the list tight.
     | E2E harness adds 127.0.0.1 so the fixture QR can be served locally.
     */
    'proxy_hosts' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('QR_PROXY_ALLOWED_HOSTS', 'tripay.co.id'))
    ))),

    'proxy_timeout' => (int) env('QR_PROXY_TIMEOUT', 5),

    'proxy_max_bytes' => (int) env('QR_PROXY_MAX_BYTES', 1048576),
];
