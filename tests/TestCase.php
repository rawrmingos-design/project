<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Disable Inertia page existence check in tests
        // Component files are built by Vite and may not be discoverable during testing
        config(['inertia.testing.ensure_pages_exist' => false]);
    }
}
