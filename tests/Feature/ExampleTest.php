<?php

namespace Tests\Feature;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Tests\TestCase;

class ExampleTest extends TestCase
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

    /**
     * Halaman root '/' permanently redirects ke homepage bahasa Indonesia.
     */
    public function test_example()
    {
        $response = $this->get('http://public.istanatopup.test/');

        $response->assertStatus(301);
        $response->assertRedirect('/id');
    }
}

