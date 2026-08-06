<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],

    'google_analytics' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
    ],

    'tenant_subscription' => [
        'webhook_token' => env('TENANT_SUBSCRIPTION_WEBHOOK_TOKEN', ''),
    ],

    'webpush' => [
        'vapid' => [
            'subject' => env('WEBPUSH_VAPID_SUBJECT'),
            'public_key' => env('WEBPUSH_VAPID_PUBLIC_KEY'),
            'private_key' => env('WEBPUSH_VAPID_PRIVATE_KEY'),
        ],
    ],

    'telegram-bot-api' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    ],

    'fonnte' => [
        'device_token' => env('FONNTE_DEVICE_TOKEN'),
    ],

    'tiktok' => [
        'pixel_id' => env('TIKTOK_PIXEL_ID'),
        'access_token' => env('TIKTOK_ACCESS_TOKEN'),
        'test_event_code' => env('TIKTOK_TEST_EVENT_CODE'),
    ],

];
