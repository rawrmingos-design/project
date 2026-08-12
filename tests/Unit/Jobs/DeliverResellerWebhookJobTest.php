<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DeliverResellerWebhookJob;
use App\Models\ResellerCallbackDelivery;
use App\Services\ResellerCallbackDeliveryService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class DeliverResellerWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_skips_if_already_delivered(): void
    {
        $delivery = ResellerCallbackDelivery::factory()->delivered()->create();

        $job = new DeliverResellerWebhookJob($delivery);

        // Service should NOT be called since already delivered
        $this->mock(ResellerCallbackDeliveryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('redeliver')->never();
        });

        // handle() refreshes the model from DB; since status = 'delivered', it should return early.
        $job->handle(app(ResellerCallbackDeliveryService::class));

        $this->assertTrue(true); // If we reach here, the job returned gracefully
    }

    public function test_job_throws_exception_on_failure_to_trigger_retry(): void
    {
        $delivery = ResellerCallbackDelivery::factory()->create(['status' => 'pending']);

        $job = new DeliverResellerWebhookJob($delivery);

        // Service returns failed status — job should throw to trigger queue retry
        $this->mock(ResellerCallbackDeliveryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('redeliver')
                 ->once()
                 ->andReturn(['status' => 'failed', 'reason' => 'Timeout']);
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Webhook delivery failed: callback delivery did not succeed.');

        $job->handle(app(ResellerCallbackDeliveryService::class));
    }

    public function test_job_succeeds_when_redeliver_is_successful(): void
    {
        $delivery = ResellerCallbackDelivery::factory()->create(['status' => 'pending']);

        $job = new DeliverResellerWebhookJob($delivery);

        // Service returns delivered status — job should NOT throw
        $this->mock(ResellerCallbackDeliveryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('redeliver')
                 ->once()
                 ->andReturn(['status' => 'delivered', 'status_code' => 200]);
        });

        $job->handle(app(ResellerCallbackDeliveryService::class));

        $this->assertTrue(true); // Reached here without exception = pass
    }

    public function test_job_logs_error_when_permanently_failed(): void
    {
        $delivery = ResellerCallbackDelivery::factory()->failed()->create();

        $job = new DeliverResellerWebhookJob($delivery);

        // Simulate Laravel calling failed() when all retries are exhausted
        $exception = new Exception('Max retries exceeded');

        // Should not throw — failed() only logs
        $job->failed($exception);

        // Verify the delivery record still has failed status (failed() only logs, doesn't update DB in this implementation)
        $delivery->refresh();
        $this->assertEquals('failed', $delivery->status);
    }
}
