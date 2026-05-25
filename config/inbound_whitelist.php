<?php

return [
    'cache_prefix' => 'inbound_whitelist',
    'cache_ttl_seconds' => 300,
    'default_mode' => 'log_only',
    'deny_status' => 403,
    'deny_body' => [
        'message' => 'Forbidden',
    ],
];
