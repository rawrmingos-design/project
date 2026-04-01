<?php

namespace Tests\Feature;

use App\Jobs\CheckProviderBalanceJob;
use App\Models\Provider;
use App\Services\ProviderBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProviderBalanceServiceGracefulFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_returns_graceful_failure_for_unsupported_provider(): void
    {
        $provider = Provider::query()->create([
            'code' => 'manual',
            'name' => 'Manual Provider',
            'is_active' => true,
            'balance' => 100000,
        ]);

        $result = app(ProviderBalanceService::class)->sync($provider);

        $this->assertFalse($result['success']);
        $this->assertSame(100000.0, (float) $result['balance']);
        $this->assertStringContainsString('tidak didukung', strtolower((string) $result['message']));
    }

    public function test_check_provider_balance_job_does_not_throw_when_service_returns_non_success(): void
    {
        $provider = Provider::query()->create([
            'code' => 'manual',
            'name' => 'Manual Provider',
            'is_active' => true,
            'balance' => 50000,
        ]);

        $service = Mockery::mock(ProviderBalanceService::class);
        $service->shouldReceive('sync')
            ->once()
            ->andReturn([
                'success' => false,
                'balance' => 50000.0,
                'message' => 'Provider tidak didukung untuk check balance: manual',
            ]);

        $job = new CheckProviderBalanceJob($provider->id);

        $job->handle($service);

        $this->assertTrue(true);
    }
}
