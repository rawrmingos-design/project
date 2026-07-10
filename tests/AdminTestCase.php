<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class AdminTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Creates the application and sets admin domain BEFORE boot.
     */
    public function createApplication(): Application
    {
        // Set admin domain BEFORE application boots (critical for Filament route registration)
        $adminDomain = $_ENV['FILAMENT_ADMIN_DOMAIN'] ?? 'admin.imhaf.online';
        $_SERVER['HTTP_HOST'] = $adminDomain;
        $_SERVER['SERVER_NAME'] = $adminDomain;

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable Inertia page existence check in tests
        config(['inertia.testing.ensure_pages_exist' => false]);
    }
}
