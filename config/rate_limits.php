<?php

return [
    'callbacks' => [
        'bot_webhook_per_minute' => (int) env('RATE_LIMIT_BOT_WEBHOOK_PER_MINUTE', 60),
        'bot_invalid_per_minute' => (int) env('RATE_LIMIT_BOT_INVALID_PER_MINUTE', 20),
        'link_per_sender_per_minute' => (int) env('RATE_LIMIT_LINK_PER_SENDER_PER_MINUTE', 5),
        'deposit_per_sender_per_minute' => (int) env('RATE_LIMIT_DEPOSIT_PER_SENDER_PER_MINUTE', 10),
        'razer_per_minute' => (int) env('RATE_LIMIT_RAZER_CALLBACK_PER_MINUTE', 180),
        'supplier_per_minute' => (int) env('RATE_LIMIT_SUPPLIER_CALLBACK_PER_MINUTE', 240),
        'payment_per_minute' => (int) env('RATE_LIMIT_PAYMENT_CALLBACK_PER_MINUTE', 180),
        'subscription_per_minute' => (int) env('RATE_LIMIT_SUBSCRIPTION_CALLBACK_PER_MINUTE', 120),
        'provider_webhook_per_minute' => (int) env('RATE_LIMIT_PROVIDER_WEBHOOK_PER_MINUTE', 240),
    ],
];
