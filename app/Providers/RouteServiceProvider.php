<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use App\Support\WhatsappNumberNormalizer;
use App\Tenancy\TenantContext;
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
            $username = $this->fingerprint([$request->input('username')]);

            return [
                Limit::perMinute(15)->by('login-ip:' . $request->ip()),
                Limit::perMinute(8)->by('login-user:' . $request->ip() . '|' . $username),
            ];
        });

        RateLimiter::for('public-register', function (Request $request) {
            $email = $this->fingerprint([$request->input('email')]);
            $phone = $this->fingerprint([
                preg_replace('/\D+/', '', (string) $request->input('no_wa', '')),
            ]);

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

        RateLimiter::for('public-order-price', fn (Request $request) =>
            Limit::perMinute(30)->by('order-price:' . $this->tenantIpKey($request))
        );

        RateLimiter::for('public-order-confirm', fn (Request $request) =>
            Limit::perMinute(10)->by('order-confirm:' . $this->tenantIpKey($request))
        );

        RateLimiter::for('public-account-check', function (Request $request) {
            $target = $this->fingerprint([
                $request->input('kategori_id', $request->input('category_id', $request->input('category'))),
                $request->input('uid', $request->input('user_id', $request->input('id'))),
                $request->input('zone', $request->input('server')),
            ]);

            return [
                Limit::perMinute(10)->by('account-check:' . $this->tenantIpKey($request)),
                Limit::perMinutes(60, 30)->by('account-check-hour:' . $request->ip()),
                Limit::perMinute(6)->by('account-check-target:' . $this->tenantKey() . ':' . $target),
            ];
        });

        RateLimiter::for('public-order-submit', function (Request $request) {
            return [
                Limit::perMinute(5)->by('order-submit:' . $this->tenantIpKey($request)),
                Limit::perMinute(5)->by('order-submit-actor:' . $this->actorKey($request)),
            ];
        });

        RateLimiter::for('public-status', function (Request $request) {
            $order = $request->route('order') ?? $request->route('order_id') ?? $request->route('invoice');

            return [
                Limit::perMinute(30)->by('public-status-ip:' . $request->ip()),
                Limit::perMinute(10)->by('public-status-order:' . $this->fingerprint([$order])),
            ];
        });

        RateLimiter::for('public-voucher', fn (Request $request) =>
            Limit::perMinute(30)->by('public-voucher:' . $this->tenantIpKey($request))
        );

        RateLimiter::for('supplier-callback', fn (Request $request) =>
            Limit::perMinute(max(1, (int) config('rate_limits.callbacks.supplier_per_minute', 240)))
                ->by('supplier-callback:' . $this->callbackKey($request))
        );

        RateLimiter::for('payment-callback', fn (Request $request) =>
            Limit::perMinute(max(1, (int) config('rate_limits.callbacks.payment_per_minute', 180)))
                ->by('payment-callback:' . $this->callbackKey($request))
        );

        RateLimiter::for('subscription-callback', fn (Request $request) =>
            Limit::perMinute(max(1, (int) config('rate_limits.callbacks.subscription_per_minute', 120)))
                ->by('subscription-callback:' . $this->callbackKey($request))
        );

        RateLimiter::for('razer-callback', fn (Request $request) =>
            Limit::perMinute(max(1, (int) config('rate_limits.callbacks.razer_per_minute', 180)))
                ->by('razer-callback:' . $request->ip())
        );

        RateLimiter::for('public-api-read', fn (Request $request) =>
            Limit::perMinute(120)->by('public-api-read:' . $this->tenantIpKey($request))
        );

        RateLimiter::for('public-api-expensive-read', fn (Request $request) =>
            Limit::perMinute(30)->by('public-api-expensive-read:' . $this->tenantIpKey($request))
        );

        RateLimiter::for('public-invoice-create', function (Request $request) {
            $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
            $keyFingerprint = $idempotencyKey !== ''
                ? $this->fingerprint([$idempotencyKey])
                : $this->fingerprint([$this->tenantIpKey($request), 'missing']);

            return [
                Limit::perMinute(5)->by('invoice-create:' . $this->tenantIpKey($request)),
                Limit::perMinute(5)->by('invoice-create-key:' . $keyFingerprint),
            ];
        });

        RateLimiter::for('provider-webhook', fn (Request $request) =>
            Limit::perMinute(max(1, (int) config('rate_limits.callbacks.provider_webhook_per_minute', 240)))
                ->by('provider-webhook:' . $this->callbackKey($request))
        );

        RateLimiter::for('bot-webhook', fn (Request $request) =>
            Limit::perMinute(max(1, (int) config('rate_limits.callbacks.bot_webhook_per_minute', 60)))
                ->by('bot-webhook:ip:' . $request->ip())
        );

        RateLimiter::for('bot-invalid', fn (Request $request) =>
            Limit::perMinute(max(1, (int) config('rate_limits.callbacks.bot_invalid_per_minute', 20)))
                ->by('bot-invalid:ip:' . $request->ip())
        );

        RateLimiter::for('bot-link', function (Request $request) {
            $sender = WhatsappNumberNormalizer::normalize(
                (string) $request->input('sender', ''),
            );
            $identity = $sender ?: 'ip:' . $request->ip();

            return Limit::perMinute(max(1, (int) config('rate_limits.callbacks.link_per_sender_per_minute', 5)))
                ->by('bot-link:' . $this->fingerprint([$identity]));
        });

        RateLimiter::for('bot-deposit', function (Request $request) {
            $sender = WhatsappNumberNormalizer::normalize(
                (string) $request->input('sender', ''),
            );
            $identity = $sender ?: 'ip:' . $request->ip();

            return Limit::perMinute(max(1, (int) config('rate_limits.callbacks.deposit_per_sender_per_minute', 10)))
                ->by('bot-deposit:' . $this->fingerprint([$identity]));
        });

        RateLimiter::for('public-search', fn (Request $request) =>
            Limit::perMinute(30)->by('public-search:' . $this->tenantIpKey($request))
        );

        RateLimiter::for('public-transaction-lookup', function (Request $request) {
            $lookup = $request->input('order_id', $request->input('invoice', $request->input('search')));

            return [
                Limit::perMinute(10)->by('transaction-lookup-ip:' . $request->ip()),
                Limit::perMinute(5)->by('transaction-lookup-target:' . $this->fingerprint([$lookup])),
            ];
        });

        RateLimiter::for('security-settings', fn (Request $request) =>
            Limit::perMinute(5)->by('security-settings:' . $this->actorKey($request))
        );

        RateLimiter::for('critical-security-settings', fn (Request $request) =>
            Limit::perMinute(3)->by('critical-security-settings:' . $this->actorKey($request))
        );

        RateLimiter::for('reseller-credential-mutation', fn (Request $request) =>
            Limit::perMinute(3)->by('reseller-credential-mutation:' . $this->actorKey($request))
        );

        RateLimiter::for('reseller-sandbox-mutation', fn (Request $request) =>
            Limit::perMinute(10)->by('reseller-sandbox-mutation:' . $this->actorKey($request))
        );

        RateLimiter::for('reseller-notification-mutation', fn (Request $request) =>
            Limit::perMinute(20)->by('reseller-notification-mutation:' . $this->actorKey($request))
        );

        RateLimiter::for('admin-external-read', fn (Request $request) =>
            Limit::perMinute(20)->by('admin-external-read:' . $this->actorKey($request))
        );

        RateLimiter::for('admin-provider-sync', fn (Request $request) =>
            Limit::perMinute(2)->by('admin-provider-sync:' . $this->actorKey($request))
        );

        RateLimiter::for('admin-order-retry', function (Request $request) {
            $order = $request->route('order_id') ?? $request->route('order');

            return [
                Limit::perMinute(3)->by('admin-order-retry:' . $this->actorKey($request)),
                Limit::perMinute(2)->by('admin-order-retry-target:' . $this->fingerprint([$order])),
            ];
        });

        RateLimiter::for('admin-financial-mutation', fn (Request $request) =>
            Limit::perMinute(3)->by('admin-financial-mutation:' . $this->actorKey($request))
        );

        RateLimiter::for('admin-bulk-mutation', fn (Request $request) =>
            Limit::perMinute(3)->by('admin-bulk-mutation:' . $this->actorKey($request))
        );

        RateLimiter::for('admin-critical', fn (Request $request) =>
            Limit::perMinute(2)->by('admin-critical:' . $this->actorKey($request))
        );

        RateLimiter::for('livewire-upload', function (Request $request) {
            return [
                Limit::perMinute(10)->by('livewire-upload-actor:' . $this->actorKey($request)),
                Limit::perMinute(20)->by('livewire-upload-ip:' . $request->ip()),
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

    private function tenantIpKey(Request $request): string
    {
        return $this->tenantKey() . ':ip:' . $request->ip();
    }

    private function tenantKey(): string
    {
        $tenantId = app(TenantContext::class)->id();

        return $tenantId ? 'tenant:' . $tenantId : 'tenant:public';
    }

    private function actorKey(Request $request): string
    {
        $userId = optional($request->user())->id;

        return $userId ? 'user:' . $userId : 'guest-ip:' . $request->ip();
    }

    private function callbackKey(Request $request): string
    {
        $route = $request->route();
        $provider = is_object($route) ? $route->parameter('provider') : null;
        $routeName = is_object($route) ? $route->getName() : null;
        $identity = $provider ?: $routeName ?: $request->path();

        return $this->fingerprint([$identity]) . ':ip:' . $request->ip();
    }

    private function fingerprint(array $values): string
    {
        $normalized = array_map(
            static fn (mixed $value): string => mb_strtolower(trim((string) $value)),
            $values,
        );

        return hash_hmac('sha256', implode('|', $normalized), (string) config('app.key'));
    }
}
