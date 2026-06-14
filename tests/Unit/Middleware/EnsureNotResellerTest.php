<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\EnsureNotReseller;
use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EnsureNotResellerTest extends TestCase
{
    use RefreshDatabase;

    private function makeMiddleware(): EnsureNotReseller
    {
        return new EnsureNotReseller();
    }

    private function makeRequest(User $user): Request
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn () => $user);
        return $request;
    }

    private function createResellerUser(): User
    {
        $user = User::factory()->create();

        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'UNIT-TEST-' . strtoupper(uniqid()),
            'mode'             => 'live',
            'is_active'        => true,
        ]);

        return $user;
    }

    public function test_non_reseller_passes_through_middleware(): void
    {
        $user    = User::factory()->create();
        $request = $this->makeRequest($user);
        $passed  = false;

        $this->makeMiddleware()->handle($request, function () use (&$passed) {
            $passed = true;
            return response('OK');
        });

        $this->assertTrue($passed, 'Regular user should pass through middleware');
    }

    public function test_reseller_is_redirected_to_default_hub(): void
    {
        // Register a dummy route so route() helper resolves
        Route::name('reseller.dashboard')->get('/id/reseller/dashboard', fn () => 'ok');

        $user    = $this->createResellerUser();
        $request = $this->makeRequest($user);
        $passed  = false;

        $response = $this->makeMiddleware()->handle($request, function () use (&$passed) {
            $passed = true;
            return response('OK');
        });

        $this->assertFalse($passed, 'Reseller should NOT pass through middleware');
        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_reseller_redirect_includes_flash_message_for_default_target(): void
    {
        Route::name('reseller.dashboard')->get('/id/reseller/dashboard', fn () => 'ok');

        $user    = $this->createResellerUser();
        $request = $this->makeRequest($user);

        $response = $this->makeMiddleware()->handle(
            $request,
            fn () => response('OK')
            // default redirectTo = 'reseller.dashboard'
        );

        $this->assertEquals(302, $response->getStatusCode());
        // Flash message is set via session
        $this->assertEquals(
            'Halaman ini tidak tersedia untuk akun Reseller Hub.',
            session('info')
        );
    }

    public function test_reseller_redirect_uses_custom_target_when_provided(): void
    {
        Route::name('reseller.deposits')->get('/id/reseller/deposits', fn () => 'ok');

        $user    = $this->createResellerUser();
        $request = $this->makeRequest($user);

        $response = $this->makeMiddleware()->handle(
            $request,
            fn () => response('OK'),
            'reseller.deposits'  // custom redirect target
        );

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/id/reseller/deposits', $response->headers->get('Location'));
    }

    public function test_reseller_redirect_has_no_flash_message_for_custom_target(): void
    {
        Route::name('reseller.deposits')->get('/id/reseller/deposits', fn () => 'ok');

        $user    = $this->createResellerUser();
        $request = $this->makeRequest($user);

        $this->makeMiddleware()->handle(
            $request,
            fn () => response('OK'),
            'reseller.deposits'
        );

        // No flash for seamless/smart redirects
        $this->assertNull(session('info'));
    }

    public function test_null_user_passes_through_middleware(): void
    {
        $request = Request::create('/test');
        $request->setUserResolver(fn () => null);
        $passed = false;

        $this->makeMiddleware()->handle($request, function () use (&$passed) {
            $passed = true;
            return response('OK');
        });

        $this->assertTrue($passed, 'Unauthenticated request should pass through (auth middleware handles guests)');
    }
}
