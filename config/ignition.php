<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ignition Settings
    |--------------------------------------------------------------------------
    */
    
    'context_providers' => [
        // Disable problematic Livewire context provider
        // \Spatie\LaravelIgnition\ContextProviders\LaravelLivewireRequestContextProvider::class,
    ],
    
    'enable_runnable_solutions' => env('IGNITION_ENABLE_RUNNABLE_SOLUTIONS', null),
    
    'remote_sites_path' => env('IGNITION_REMOTE_SITES_PATH', ''),
    
    'local_sites_path' => env('IGNITION_LOCAL_SITES_PATH', ''),
    
    'housekeeping_endpoint_prefix' => '_ignition',
    
    'settings_file_path' => '',
    
    'recorders' => [
        \Spatie\LaravelIgnition\Recorders\DumpRecorder\DumpRecorder::class => [
            'enabled' => env('IGNITION_RECORD_DUMPS', true),
            'max_dumps' => 100,
        ],
        \Spatie\LaravelIgnition\Recorders\JobRecorder\JobRecorder::class => [
            'enabled' => env('IGNITION_RECORD_JOBS', true),
            'max_jobs' => 50,
        ],
        \Spatie\LaravelIgnition\Recorders\LogRecorder\LogRecorder::class => [
            'enabled' => env('IGNITION_RECORD_LOGS', true),
            'max_logs' => 200,
        ],
        \Spatie\LaravelIgnition\Recorders\QueryRecorder\QueryRecorder::class => [
            'enabled' => env('IGNITION_RECORD_QUERIES', true),
            'max_queries' => 500,
        ],
    ],
];
