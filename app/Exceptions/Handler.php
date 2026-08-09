<?php

namespace App\Exceptions;

use App\Support\ResellerApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

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
        if (
            $exception instanceof TooManyRequestsHttpException
            && ! $request->expectsJson()
            && $request->isMethod('post')
            && ($request->is('id/sign-in') || $request->is('id/sign-up') || $request->is('id/forgot-password') || $request->is('id/reset-password'))
        ) {
            $retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 0);

            if ($retryAfter <= 0) {
                $retryAfter = 60;
            }

            return redirect()
                ->back()
                ->withInput($request->except(['token', 'password', 'password_confirmation', 'passwordd']))
                ->withErrors([
                    'error' => $this->buildAuthThrottleMessage($request, $retryAfter),
                ]);
        }

        if (
            $exception instanceof TooManyRequestsHttpException
            && ($request->expectsJson() || $request->is('api/*'))
        ) {
            $retryAfter = (int) ($exception->getHeaders()['Retry-After'] ?? 0);

            if ($retryAfter <= 0) {
                $retryAfter = 60;
            }

            Log::warning('http.rate_limit.exceeded', [
                'route' => $request->route()?->getName(),
                'route_uri' => $request->route()?->uri(),
                'method' => $request->method(),
                'retry_after' => $retryAfter,
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
            ]);

            return ResellerApiResponse::tooManyRequests($retryAfter, $exception->getHeaders());
        }

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

    private function formatRetryAfter(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' detik';
        }

        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;

        if ($remainingSeconds === 0) {
            return $minutes . ' menit';
        }

        return $minutes . ' menit ' . $remainingSeconds . ' detik';
    }

    private function buildAuthThrottleMessage($request, int $retryAfter): string
    {
        $duration = $this->formatRetryAfter($retryAfter);

        if ($request->is('id/sign-in')) {
            return 'Terlalu banyak percobaan masuk. Coba lagi dalam ' . $duration . '.';
        }

        if ($request->is('id/sign-up')) {
            return 'Terlalu banyak percobaan daftar akun. Coba lagi dalam ' . $duration . '.';
        }

        return 'Terlalu banyak percobaan. Coba lagi dalam ' . $duration . '.';
    }
}
