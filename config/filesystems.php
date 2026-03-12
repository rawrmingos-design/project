<?php

return [

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            // Relative URL avoids cross-domain asset requests on admin subdomain.
            'url' => env('PUBLIC_DISK_URL', '/storage'),
            'visibility' => 'public',
            'throw' => false,
        ],

        'assets' => [
            'driver' => 'local',
            'root' => public_path(),
            // Empty base URL => generated URLs become "/{path}" (same-origin).
            'url' => env('ASSETS_DISK_URL', ''),
            'visibility' => 'public',
            'throw' => false,
        ],
        
        'banner' => [
            'driver' => 'local',
            'root' => public_path('banner'),
            'url' => env('BANNER_DISK_URL', '/assets/banner'),
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
        ],

    ],

];
