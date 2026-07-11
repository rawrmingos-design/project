<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Env;

abstract class AdminTestCase extends BaseTestCase
{
    use CreatesApplication;

    public function createApplication(): Application
    {
        putenv('FILAMENT_ADMIN_DOMAIN=');
        $_ENV['FILAMENT_ADMIN_DOMAIN'] = '';
        $_SERVER['FILAMENT_ADMIN_DOMAIN'] = '';
        Env::enablePutenv();

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
