<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUnknownRouteRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        putenv('APP_URL=http://public.istanatopup.test');
        putenv('FILAMENT_ADMIN_DOMAIN=admin.istanatopup.test');
        $_ENV['APP_URL'] = 'http://public.istanatopup.test';
        $_ENV['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';
        $_SERVER['APP_URL'] = 'http://public.istanatopup.test';
        $_SERVER['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    public function test_unknown_route_on_public_host_redirects_to_id_with_302(): void
    {
        $response = $this->get('http://public.istanatopup.test/random-unknown');

        $response->assertStatus(302);
        $response->assertRedirect('/id');
    }

    public function test_unknown_route_under_id_on_public_host_redirects_to_id_with_302(): void
    {
        $response = $this->get('http://public.istanatopup.test/id/random-unknown');

        $response->assertStatus(302);
        $response->assertRedirect('/id');
    }

    public function test_sign_in_and_sign_up_routes_remain_reachable(): void
    {
        $this->withoutVite();

        $this
            ->get('http://public.istanatopup.test/id/sign-in')
            ->assertOk();

        $this
            ->get('http://public.istanatopup.test/id/sign-up')
            ->assertOk();
    }

    public function test_admin_host_redirects_id_routes_to_login_with_302(): void
    {
        $this
            ->get('http://admin.istanatopup.test/id')
            ->assertStatus(302)
            ->assertRedirect('/login');

        $this
            ->get('http://admin.istanatopup.test/id/random-unknown')
            ->assertStatus(302)
            ->assertRedirect('/login');
    }
}
