<?php

namespace Tests\Feature\Tenancy;

use App\Http\Middleware\ResolveTenant;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ResolveTenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        parent::tearDown();
    }

    public function test_resolves_active_subdomain_tenant(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $tenant = $this->makeTenant([
            'subdomain' => 'gacortopup',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $request = Request::create('https://gacortopup.topupengine.test/id', 'GET');

        app(ResolveTenant::class)->handle($request, function () use ($tenant) {
            $this->assertTrue(app(TenantContext::class)->has());
            $this->assertSame($tenant->id, app(TenantContext::class)->id());
            $this->assertTrue(app()->bound('tenant'));

            return response('ok');
        });

        $this->assertFalse(app(TenantContext::class)->has());
        $this->assertFalse(app()->bound('tenant'));
    }

    public function test_does_not_resolve_suspended_tenant(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $this->makeTenant([
            'subdomain' => 'suspended-shop',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $request = Request::create('https://suspended-shop.topupengine.test/id', 'GET');

        app(ResolveTenant::class)->handle($request, function () {
            $this->assertFalse(app(TenantContext::class)->has());
            $this->assertFalse(app()->bound('tenant'));

            return response('ok');
        });
    }

    public function test_bypasses_public_admin_and_local_hosts(): void
    {
        config(['app.url' => 'https://topupengine.test']);
        putenv('FILAMENT_ADMIN_DOMAIN=admin.topupengine.test');

        foreach (['topupengine.test', 'admin.topupengine.test', 'localhost', '127.0.0.1'] as $host) {
            $request = Request::create('https://' . $host . '/id', 'GET');

            app(ResolveTenant::class)->handle($request, function () use ($host) {
                $this->assertFalse(app(TenantContext::class)->has(), 'Host should bypass tenant resolution: ' . $host);

                return response('ok');
            });
        }
    }

    private function makeTenant(array $attributes = []): Tenant
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        return Tenant::query()->create(array_merge([
            'owner_user_id' => $owner->id,
            'name' => 'Test Tenant',
            'subdomain' => 'test-tenant',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ], $attributes));
    }
}
