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
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
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
    }
}
