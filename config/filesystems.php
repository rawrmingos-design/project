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
            'url' => env('ASSET_URL', env('APP_URL')).'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'assets' => [
            'driver' => 'local',
            'root' => public_path(),
            'url' => env('ASSET_URL', env('APP_URL')),
            'visibility' => 'public',
            'throw' => false,
        ],
        
        'banner' => [
            'driver' => 'local',
            'root' => public_path('banner'),
            'url' => env('ASSET_URL', env('APP_URL')).'/assets/banner',
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
