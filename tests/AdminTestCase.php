<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class AdminTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Creates the application and ensures Filament admin routes work in tests.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();
        
        // Override app.url AFTER bootstrap but BEFORE Filament panel boots
        // This prevents Filament from using production domain in tests
        $app['config']->set('app.url', 'http://localhost');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable Inertia page existence check in tests
        config(['inertia.testing.ensure_pages_exist' => false]);
    }
}
