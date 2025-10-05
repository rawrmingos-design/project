<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        // 
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            // Skip Livewire context provider errors
            if (str_contains($e->getMessage(), 'LaravelLivewireRequestContextProvider') || 
                str_contains($e->getMessage(), 'Trying to access array offset on null')) {
                return false;
            }
            
            Log::error('Exception occurred: ' . $e->getMessage(), ['exception' => $e]);
        });
    }
    
    public function render($request, Throwable $exception)
    {
        // Handle Livewire context provider errors gracefully
        if (str_contains($exception->getMessage(), 'LaravelLivewireRequestContextProvider') || 
            str_contains($exception->getMessage(), 'Trying to access array offset on null')) {
            
            if (config('app.debug')) {
                return response()->view('errors.debug', ['exception' => $exception], 500);
            }
            
            return response()->view('errors.500', [], 500);
        }
        
        return parent::render($request, $exception);
    }
}
