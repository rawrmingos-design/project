<?php

return [
    'dns_resolution' => (bool) env('RESELLER_CALLBACK_DNS_RESOLUTION', true),
    'max_response_bytes' => (int) env('RESELLER_CALLBACK_MAX_RESPONSE_BYTES', 65536),
    'max_redirects' => 0,
];
