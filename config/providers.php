<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Provider API Configurations
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for all external provider APIs
    | used in the game top-up system.
    |
    */

    'digiflazz' => [
        'base_url' => env('DIGIFLAZZ_BASE_URL', 'https://api.digiflazz.com/v1/'),
        'username' => env('DIGIFLAZZ_USERNAME'),
        'api_key' => env('DIGIFLAZZ_API_KEY'),
        'enabled' => env('DIGIFLAZZ_ENABLED', true),
        'timeout' => env('DIGIFLAZZ_TIMEOUT', 30),
        'retry_attempts' => env('DIGIFLAZZ_RETRY_ATTEMPTS', 3),
        'webhook_secret' => env('DIGIFLAZZ_WEBHOOK_SECRET'),
    ],

    'bangjeff' => [
        'base_url' => env('BANGJEFF_BASE_URL', 'https://distribution-api.bangjeff.com'),
        'sandbox_base_url' => env('BANGJEFF_SANDBOX_BASE_URL', 'https://sandbox-api.bangjeff.com'),
        'use_sandbox_on_local' => env('BANGJEFF_USE_SANDBOX_ON_LOCAL', true),
        'region' => env('BANGJEFF_REGION', 'ID'),
        'api_id' => env('BANGJEFF_API_ID'),
        'api_key' => env('BANGJEFF_API_KEY'),
        'enabled' => env('BANGJEFF_ENABLED', true),
        'timeout' => env('BANGJEFF_TIMEOUT', 30),
        'retry_attempts' => env('BANGJEFF_RETRY_ATTEMPTS', 3),
        'webhook_secret' => env('BANGJEFF_WEBHOOK_SECRET'),
    ],

    'apigames' => [
        'base_url' => env('APIGAMES_BASE_URL', 'https://apigames.id/api/'),
        'api_id' => env('APIGAMES_API_ID'),
        'api_key' => env('APIGAMES_API_KEY'),
        'enabled' => env('APIGAMES_ENABLED', false),
        'timeout' => env('APIGAMES_TIMEOUT', 30),
        'retry_attempts' => env('APIGAMES_RETRY_ATTEMPTS', 3),
        'webhook_secret' => env('APIGAMES_WEBHOOK_SECRET'),
    ],

    'sufpayment' => [
        'base_url' => env('SUFPAYMENT_BASE_URL', 'https://sufpayment.com/api/v1'),
        'order_cmd' => env('SUFPAYMENT_ORDER_CMD', ''),
        'status_cmd' => env('SUFPAYMENT_STATUS_CMD', ''),
        'product_cmd' => env('SUFPAYMENT_PRODUCT_CMD', ''),
        'target_separator' => env('SUFPAYMENT_TARGET_SEPARATOR', ''),
        'enabled' => env('SUFPAYMENT_ENABLED', true),
        'timeout' => env('SUFPAYMENT_TIMEOUT', 15),
        'retry_attempts' => env('SUFPAYMENT_RETRY_ATTEMPTS', 1),
        'polling' => [
            'enabled' => env('SUFPAYMENT_POLLING_ENABLED', true),
            'interval_seconds' => env('SUFPAYMENT_POLLING_INTERVAL_SECONDS', 120),
            'max_attempts' => env('SUFPAYMENT_POLLING_MAX_ATTEMPTS', 30),
            'queue' => env('SUFPAYMENT_POLLING_QUEUE', 'default'),
        ],
    ],

    'check_id' => [
        'selfhosted' => [
            'enabled' => env('CHECK_ID_SELFHOSTED_ENABLED', false),
            'base_url' => env('CHECK_ID_SELFHOSTED_BASE_URL', 'https://cekid.jasakoding.web.id'),
            'api_key' => env('CHECK_ID_SELFHOSTED_API_KEY', '53db9e79b903742d8cbd8eb2417148be2b7df1a7ed8acdcffbe894d9ef435af1'),
            'timeout' => env('CHECK_ID_SELFHOSTED_TIMEOUT', 12),
            'connect_timeout' => env('CHECK_ID_SELFHOSTED_CONNECT_TIMEOUT', 5),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Provider Settings
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'auto_sync_enabled' => env('PROVIDER_AUTO_SYNC', false),
        'sync_interval' => env('PROVIDER_SYNC_INTERVAL', 3600), // seconds
        'batch_size' => env('PROVIDER_SYNC_BATCH_SIZE', 100),
        'max_execution_time' => env('PROVIDER_SYNC_MAX_TIME', 300), // seconds
    ],

    'pricing' => [
        'auto_update_prices' => env('PROVIDER_AUTO_UPDATE_PRICES', false),
        'price_update_interval' => env('PROVIDER_PRICE_UPDATE_INTERVAL', 1800), // seconds
        'markup_percentage' => env('PROVIDER_DEFAULT_MARKUP', 10), // percent
        'min_profit_margin' => env('PROVIDER_MIN_PROFIT_MARGIN', 5), // percent
    ],

    'balance' => [
        'auto_refresh' => env('PROVIDER_AUTO_REFRESH_BALANCE', true),
    ],

    'webhooks' => [
        'enabled' => env('PROVIDER_WEBHOOKS_ENABLED', true),
        'verify_signatures' => env('PROVIDER_VERIFY_WEBHOOK_SIGNATURES', true),
        'log_webhooks' => env('PROVIDER_LOG_WEBHOOKS', true),
        'timeout' => env('PROVIDER_WEBHOOK_TIMEOUT', 10),
    ],

    'fallback' => [
        'enabled' => env('PROVIDER_FALLBACK_ENABLED', true),
        'primary_provider' => env('PROVIDER_PRIMARY', 'digiflazz'),
        'fallback_providers' => [
            'bangjeff',
            'apigames',
        ],
        'max_fallback_attempts' => env('PROVIDER_MAX_FALLBACK_ATTEMPTS', 3),
    ],

    'retirement' => [
        'retired_codes' => [
            'topupedia',
            'moogold',
            'gameshop',
            'strleyashop',
            'yezzpay',
            'elitedias',
            'aoshi',
        ],
        'retained_external_codes' => [
            'digiflazz',
            'bangjeff',
            'vip',
            'vip_reseller',
            'apigames',
            'sufpayment',
        ],
        'internal_codes' => [
            'manual',
            'joki',
            'jokigendong',
            'vilogml',
            'giftskin',
        ],
    ],

    'cache' => [
        'enabled' => env('PROVIDER_CACHE_ENABLED', true),
        'ttl' => env('PROVIDER_CACHE_TTL', 300), // seconds
        'products_cache_key' => 'provider_products',
        'prices_cache_key' => 'provider_prices',
    ],

    'logging' => [
        'enabled' => env('PROVIDER_LOGGING_ENABLED', true),
        'log_requests' => env('PROVIDER_LOG_REQUESTS', true),
        'log_responses' => env('PROVIDER_LOG_RESPONSES', true),
        'log_errors' => env('PROVIDER_LOG_ERRORS', true),
        'log_channel' => env('PROVIDER_LOG_CHANNEL', 'daily'),
    ],
];
