<?php

return [
    'callbacks' => [
        'razer_per_minute' => (int) env('RATE_LIMIT_RAZER_CALLBACK_PER_MINUTE', 180),
        'supplier_per_minute' => (int) env('RATE_LIMIT_SUPPLIER_CALLBACK_PER_MINUTE', 240),
        'payment_per_minute' => (int) env('RATE_LIMIT_PAYMENT_CALLBACK_PER_MINUTE', 180),
        'subscription_per_minute' => (int) env('RATE_LIMIT_SUBSCRIPTION_CALLBACK_PER_MINUTE', 120),
        'provider_webhook_per_minute' => (int) env('RATE_LIMIT_PROVIDER_WEBHOOK_PER_MINUTE', 240),
    ],
];
