<?php

namespace Tests;

use App\Services\CheckId\CheckIdResolver;
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

    protected function mockSuccessfulAccountValidation(string $username = 'Test Player'): void
    {
        $this->partialMock(CheckIdResolver::class, function ($mock) use ($username): void {
            $mock->shouldReceive('resolveForCategory')->andReturn([
                'status' => ['code' => 200, 'message' => 'Success'],
                'data' => ['username' => $username],
            ]);
        });
    }
}
