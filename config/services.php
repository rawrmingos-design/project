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
        'bot_scope' => env('TELEGRAM_BOT_SCOPE', 'default'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', ''),
        'link_challenge_expiry_minutes' => env('TELEGRAM_LINK_CHALLENGE_EXPIRY_MINUTES', 10),
        'link_challenge_max_attempts' => env('TELEGRAM_LINK_CHALLENGE_MAX_ATTEMPTS', 5),
        'deposit_enabled' => env('TELEGRAM_DEPOSIT_ENABLED', false),
        'order_enabled' => env('BOT_ORDER_ENABLED', false),
        'admin_contact_url' => env('TELEGRAM_ADMIN_CONTACT_URL', ''),
        'required_channel' => [
            'enabled' => env('TELEGRAM_REQUIRED_CHANNEL_ENABLED', false),
            'id' => env('TELEGRAM_REQUIRED_CHANNEL_ID'),
            'url' => env('TELEGRAM_REQUIRED_CHANNEL_URL'),
            // Keanggotaan SELALU dicek ulang ke Telegram pada setiap request,
            // jadi tidak ada TTL hasil positif. Pencabutan keanggotaan harus
            // langsung terasa (bug: user keluar channel masih lolos 24 jam).
            // Berapa lama fakta "user ini pernah lolos" diingat sebagai jaring
            // pengaman saat Telegram TIDAK BISA dihubungi. Tidak pernah dipakai
            // untuk meloloskan request yang berhasil diperiksa.
            'grace_seconds' => env('TELEGRAM_REQUIRED_CHANNEL_GRACE_SECONDS', 86400),
            // Tujuan laporan saat gate TIDAK BISA berfungsi (bot belum ada di
            // channel). Kosong = cukup lewat log.
            'admin_alert_chat_id' => env('TELEGRAM_ADMIN_ALERT_CHAT_ID'),
        ],
        // Deep-link grup diskusi (opsional) untuk tombol "Diskusi".
        // Catatan: Bot API TIDAK bisa membuat post di topik General; tautan
        // ini yang dipakai agar user bisa masuk ke topik diskusi.
        'discussion_url' => env('TELEGRAM_DISCUSSION_URL'),
        // Tujuan pengumuman admin: daftar {label, chat_id, thread_id}.
        // Diisi dari kolom JSON setting_webs.telegram_announcement_targets.
        'announcement_targets' => [],
    ],

    'fonnte' => [
        'device_token' => env('FONNTE_DEVICE_TOKEN'),
    ],

    'razerpay' => [
        'secret_key' => env('RAZERPAY_SECRET_KEY'),
    ],

    'tiktok' => [
        'pixel_id' => env('TIKTOK_PIXEL_ID'),
        'access_token' => env('TIKTOK_ACCESS_TOKEN'),
        'test_event_code' => env('TIKTOK_TEST_EVENT_CODE'),
    ],

];
