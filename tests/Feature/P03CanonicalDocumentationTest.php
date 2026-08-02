<?php

namespace Tests\Feature;

use App\Http\Controllers\Public\DocsController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class P03CanonicalDocumentationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function createApplication()
    {
        $this->savedEnv = [
            'APP_URL' => getenv('APP_URL'),
            'FILAMENT_ADMIN_DOMAIN' => getenv('FILAMENT_ADMIN_DOMAIN'),
            'DOCS_DOMAIN' => getenv('DOCS_DOMAIN'),
        ];

        $this->setEnvironment('APP_URL', 'http://public.istanatopup.test');
        $this->setEnvironment('FILAMENT_ADMIN_DOMAIN', 'admin.istanatopup.test');
        $this->setEnvironment('DOCS_DOMAIN', 'docs.istanatopup.test');
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
                $this->setEnvironment($key, $value);
            }
        }

        Env::enablePutenv();
    }

    public function test_docs_host_owns_the_configured_documentation_portal(): void
    {
        $user = User::factory()->create(['role' => 'Member']);

        $this->actingAs($user)
            ->get('http://docs.istanatopup.test/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Docs/Index', false));

        $route = Route::getRoutes()->getByName('docs.index');

        $this->assertNotNull($route);
        $this->assertSame('docs.istanatopup.test', $route->getDomain());
        $this->assertSame(DocsController::class . '@index', $route->getActionName());
    }

    public function test_storefront_legacy_documentation_paths_follow_the_public_unknown_route_policy(): void
    {
        $this->get('http://public.istanatopup.test/id/docs')
            ->assertStatus(302)
            ->assertRedirect('/id');

        $this->get('http://public.istanatopup.test/api-documentation')
            ->assertStatus(302)
            ->assertRedirect('/id');

        $this->assertNull(Route::getRoutes()->getByName('docs'));
    }

    public function test_retired_cek_region_follows_the_public_unknown_route_policy(): void
    {
        $this->get('http://public.istanatopup.test/id/cek-region')
            ->assertStatus(302)
            ->assertRedirect('/id');

        $this->assertFalse(collect(Route::getRoutes()->getRoutes())
            ->contains(fn ($route) => $route->uri() === 'id/cek-region'));
    }

    private function setEnvironment(string $key, string $value): void
    {
        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
