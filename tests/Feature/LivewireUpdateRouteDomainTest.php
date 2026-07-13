<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LivewireUpdateRouteDomainTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $savedEnv = [];

    public function createApplication()
    {
        $this->savedEnv = [
            'APP_URL' => getenv('APP_URL'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
        ];

        putenv('APP_URL=http://public.istanatopup.test');
        putenv('FILAMENT_ADMIN_DOMAIN=admin.istanatopup.test');
        $_ENV['APP_URL'] = 'http://public.istanatopup.test';
        $_ENV['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';
        $_SERVER['APP_URL'] = 'http://public.istanatopup.test';
        $_SERVER['FILAMENT_ADMIN_DOMAIN'] = 'admin.istanatopup.test';

        Env::enablePutenv();

        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        Env::enablePutenv();
    }

    public function test_livewire_update_route_exists_for_public_and_admin_hosts(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->uri() === 'livewire/update' && in_array('POST', $route->methods(), true))
            ->values();

        $this->assertCount(2, $routes);
        $this->assertNotNull($routes->first(fn ($route) => $route->getName() === 'default.livewire.update' && $route->getDomain() === null));
        $this->assertNotNull($routes->first(fn ($route) => $route->getName() === 'filament.livewire.update' && $route->getDomain() === 'admin.istanatopup.test'));
    }

    public function test_public_host_matches_default_livewire_update_route(): void
    {
        $request = Request::create('http://public.istanatopup.test/livewire/update', 'POST');
        $route = Route::getRoutes()->match($request);

        $this->assertSame('default.livewire.update', $route->getName());
    }

    public function test_admin_host_matches_filament_livewire_update_route(): void
    {
        $request = Request::create('http://admin.istanatopup.test/livewire/update', 'POST');
        $route = Route::getRoutes()->match($request);

        $this->assertSame('filament.livewire.update', $route->getName());
    }
}
