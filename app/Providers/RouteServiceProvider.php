<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(240)->by('api-ip:' . $request->ip());
        });

        RateLimiter::for('reseller-api-balance', function (Request $request) {
            return $this->resellerApiLimits($request, 30, 120, 'balance');
        });

        RateLimiter::for('reseller-api-category', function (Request $request) {
            return $this->resellerApiLimits($request, 60, 180, 'category');
        });

        RateLimiter::for('reseller-api-variant', function (Request $request) {
            return $this->resellerApiLimits($request, 90, 240, 'variant');
        });

        RateLimiter::for('reseller-api-order', function (Request $request) {
            return $this->resellerApiLimits($request, 20, 60, 'order');
        });

        RateLimiter::for('reseller-api-status', function (Request $request) {
            return $this->resellerApiLimits($request, 180, 300, 'status');
        });

        RateLimiter::for('public-login', function (Request $request) {
            $username = mb_strtolower(trim((string) $request->input('username', '')));

            return [
                Limit::perMinute(15)->by('login-ip:' . $request->ip()),
                Limit::perMinute(8)->by('login-user:' . $request->ip() . '|' . $username),
            ];
        });

        RateLimiter::for('public-register', function (Request $request) {
            $email = mb_strtolower(trim((string) $request->input('email', '')));
            $phone = preg_replace('/\D+/', '', (string) $request->input('no_wa', ''));

            return [
                Limit::perMinute(8)->by('register-ip:' . $request->ip()),
                Limit::perMinute(4)->by('register-email:' . $request->ip() . '|' . $email),
                Limit::perMinute(4)->by('register-phone:' . $request->ip() . '|' . $phone),
            ];
        });

        RateLimiter::for('password-recovery-request', function (Request $request) {
            $username = mb_strtolower(trim((string) $request->input('username', '')));
            $identifier = hash_hmac('sha256', $username, (string) config('app.key'));

            return [
                Limit::perMinute(5)->by('password-recovery-ip:' . $request->ip()),
                Limit::perMinutes(60, 3)->by('password-recovery-identifier:' . $identifier),
            ];
        });

        RateLimiter::for('password-reset-submit', function (Request $request) {
            $token = (string) $request->input('token', '');
            $tokenFingerprint = hash_hmac('sha256', $token, (string) config('app.key'));

            return [
                Limit::perMinute(5)->by('password-reset-ip:' . $request->ip()),
                Limit::perMinutes(60, 3)->by('password-reset-token:' . $tokenFingerprint),
            ];
        });

        RateLimiter::for('public-affiliate-request', function (Request $request) {
            $userKey = (string) (optional($request->user())->id ?: 'guest');

            return [
                Limit::perMinute(3)->by('affiliate-request-user:' . $userKey),
                Limit::perMinute(8)->by('affiliate-request-ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('public-deposit-submit', function (Request $request) {
            $userKey = (string) (optional($request->user())->id ?: 'guest');

            return [
                Limit::perMinute(6)->by('deposit-submit-user:' . $userKey),
                Limit::perMinute(12)->by('deposit-submit-ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('public-withdrawal-submit', function (Request $request) {
            $userKey = (string) (optional($request->user())->id ?: 'guest');

            return [
                Limit::perMinute(2)->by('withdraw-submit-user:' . $userKey),
                Limit::perMinute(6)->by('withdraw-submit-ip:' . $request->ip()),
            ];
        });

        // Rate limit for manual callback resend via Reseller Hub.
        // Keyed by authenticated user ID so each user has their own independent bucket.
        // 10 requests/minute allows reasonable retry workflow without enabling abuse.
        RateLimiter::for('reseller-callback-resend', function (Request $request) {
            $userKey = (string) (optional($request->user())->id ?: 'guest-' . $request->ip());

            return Limit::perMinute(10)->by('callback-resend-user:' . $userKey);
        });

        // Phase 5 — Task 5.3
        // Rate limit for sandbox webhook test trigger.
        // Very conservative (5/minute) — this is a manual debug tool, not a production flow.
        RateLimiter::for('reseller-callback-test', function (Request $request) {
            $userKey = (string) (optional($request->user())->id ?: 'guest-' . $request->ip());

            return Limit::perMinute(5)->by('callback-test-user:' . $userKey);
        });
    }

    private function resellerApiLimits(Request $request, int $tokenLimit, int $ipLimit, string $segment): array
    {
        $token = trim((string) $request->bearerToken());
        $tokenKey = $token === ''
            ? 'missing-token:' . $request->ip()
            : 'token:' . hash('sha256', $token);

        return [
            Limit::perMinute($tokenLimit)->by('reseller-api:' . $segment . ':' . $tokenKey),
            Limit::perMinute($ipLimit)->by('reseller-api:' . $segment . ':ip:' . $request->ip()),
        ];
    }
}
