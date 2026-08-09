<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SensitiveRouteThrottleTest extends TestCase
{
    public function test_public_cron_and_legacy_maintenance_routes_are_not_registered(): void
    {
        foreach ([
            '/cronjob/update-gameshop',
            '/cronjob/update-strleyashop',
            '/cronjob/update-elitedias',
            '/cronjob/update-yezzpay',
            '/weji-mt',
            '/weji-up',
        ] as $uri) {
            $registered = collect(Route::getRoutes()->getRoutes())
                ->contains(fn ($route): bool => $route->uri() === ltrim($uri, '/'));

            $this->assertFalse($registered, $uri . ' must not be registered.');
        }
    }

    public function test_stage_one_routes_use_dedicated_limiters(): void
    {
        $routes = [
            ['POST', '/callback/razerpay', 'razer-callback'],
            ['POST', '/id/harga', 'public-order-price'],
            ['POST', '/id/konfirmasi-data', 'public-order-confirm'],
            ['POST', '/ajax/check-account', 'public-account-check'],
            ['POST', '/id', 'public-order-submit'],
            ['GET', '/ajax/transaction-status/INV-1', 'public-status'],
            ['POST', '/check-voucher', 'public-voucher'],
            ['POST', '/wejizy/digi/payload', 'supplier-callback'],
            ['POST', '/wejizy/duitku/callback', 'payment-callback'],
            ['POST', '/api/auth/login', 'public-login'],
            ['POST', '/api/auth/register', 'public-register'],
        ];

        foreach ($routes as [$method, $uri, $limiter]) {
            $this->assertRouteHasThrottle($method, $uri, $limiter);
        }
    }

    public function test_stage_two_routes_use_cost_specific_limiters(): void
    {
        $routes = [
            ['GET', '/api/home', 'public-api-read'],
            ['GET', '/api/price-list', 'public-api-expensive-read'],
            ['POST', '/api/v2/order/store', 'public-order-submit'],
            ['GET', '/api/v2/order/status/INV-1', 'public-status'],
            ['POST', '/api/gateway/check-id', 'public-account-check'],
            ['POST', '/api/gateway/invoices', 'public-invoice-create'],
            ['POST', '/api/webhooks/digiflazz', 'provider-webhook'],
            ['GET', '/id/search/products', 'public-search'],
            ['POST', '/id/cari', 'public-transaction-lookup'],
            ['POST', '/id/settings/api-key/regenerate', 'critical-security-settings'],
            ['POST', '/id/reseller/credentials/rotate-live', 'reseller-credential-mutation'],
            ['POST', '/id/reseller/sandbox/simulate', 'reseller-sandbox-mutation'],
        ];

        foreach ($routes as [$method, $uri, $limiter]) {
            $this->assertRouteHasThrottle($method, $uri, $limiter);
        }
    }

    public function test_recent_purchases_has_one_canonical_api_route(): void
    {
        $matching = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => $route->uri() === 'api/recent-purchases')
            ->values();

        $this->assertCount(1, $matching);
        $this->assertSame('recent-purchases.index', $matching->first()->getName());
        $this->assertContains('api', $matching->first()->gatherMiddleware());
        $this->assertContains('throttle:public-api-read', $matching->first()->gatherMiddleware());
    }

    public function test_stage_three_admin_routes_use_action_specific_limiters(): void
    {
        $routes = [
            ['GET', '/bangjeff/balance', 'admin-external-read'],
            ['POST', '/produk/sync/', 'admin-provider-sync'],
            ['GET', '/process-order/INV-1', 'admin-order-retry'],
            ['POST', '/layanan/bulk-delete', 'admin-bulk-mutation'],
        ];

        foreach ($routes as [$method, $uri, $limiter]) {
            $this->assertRouteHasThrottle($method, $uri, $limiter);
        }
    }

    private function assertRouteHasThrottle(string $method, string $uri, string $limiter): void
    {
        $route = $this->findRoute($method, $uri);

        $this->assertNotNull($route, $method . ' ' . $uri . ' was not registered.');
        $this->assertContains('throttle:' . $limiter, $route->gatherMiddleware());
    }

    private function findRoute(string $method, string $uri): mixed
    {
        try {
            $route = Route::getRoutes()->match(Request::create($uri, $method));
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException|\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
            return null;
        }

        return $route;
    }
}
