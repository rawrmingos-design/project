<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicUnknownRouteRedirectTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    public function createApplication()
    {
        // Save originals before overriding (reads from OS-level putenv, not Laravel's static repo)
        $this->savedEnv = [
            'APP_URL'               => getenv('APP_URL'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
        ];

        putenv('APP_URL=http://public.istanatopup.test');
        putenv('FILAMENT_ADMIN_DOMAIN=admin.istanatopup.test');
        $_ENV['APP_URL']               = 'http://public.istanatopup.test';
        $_ENV['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';
        $_SERVER['APP_URL']               = 'http://public.istanatopup.test';
        $_SERVER['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';

        // Reset the STATIC Env::$repository so the next getRepository() call creates a fresh one
        // that reads from our putenv values above — not the cached values from a previous test class
        Env::enablePutenv();

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure no stale setting_webs rows leak from prior test classes
        // (createApplication() override can break RefreshDatabase transaction boundaries)
        DB::table('setting_webs')->delete();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore original env values so subsequent test classes see the real .env values
        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key]    = $value;
                $_SERVER[$key] = $value;
            }
        }

        // Reset static repository so the next test class bootstraps fresh from the restored putenv
        Env::enablePutenv();
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
