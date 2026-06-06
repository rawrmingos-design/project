<?php

namespace Tests\Unit\Jobs;

use App\Jobs\DeliverResellerWebhookJob;
use App\Models\ResellerCallbackLog;
use App\Services\ResellerCallbackDeliveryService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeliverResellerWebhookJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_skips_if_already_delivered(): void
    {
        $delivery = ResellerCallbackLog::factory()->create([
            'status' => 'delivered', // Already delivered
        ]);

        $job = new DeliverResellerWebhookJob($delivery);

        // Service should NOT be called
        $this->mock(ResellerCallbackDeliveryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('redeliver')->never();
        });

        $job->handle();

        // The job should return gracefully without doing anything
        $this->assertTrue(true);
    }

    public function test_job_throws_exception_on_failure_to_trigger_retry(): void
    {
        $delivery = ResellerCallbackLog::factory()->create([
            'status' => 'pending',
        ]);

        $job = new DeliverResellerWebhookJob($delivery);

        // Service returns failed status
        $this->mock(ResellerCallbackDeliveryService::class, function (MockInterface $mock) use ($delivery) {
            $mock->shouldReceive('redeliver')
                 ->once()
                 ->with($delivery)
                 ->andReturn(['status' => 'failed', 'message' => 'Timeout']);
        });

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Webhook delivery failed: Timeout');

        $job->handle();
    }

    public function test_job_succeeds_when_redeliver_is_successful(): void
    {
        $delivery = ResellerCallbackLog::factory()->create([
            'status' => 'pending',
        ]);

        $job = new DeliverResellerWebhookJob($delivery);

        // Service returns delivered status
        $this->mock(ResellerCallbackDeliveryService::class, function (MockInterface $mock) use ($delivery) {
            $mock->shouldReceive('redeliver')
                 ->once()
                 ->with($delivery)
                 ->andReturn(['status' => 'delivered', 'message' => 'OK']);
        });

        // Should not throw any exception
        $job->handle();
        $this->assertTrue(true);
    }

    public function test_job_logs_error_when_permanently_failed(): void
    {
        $delivery = ResellerCallbackLog::factory()->create([
            'status' => 'failed',
        ]);

        $job = new DeliverResellerWebhookJob($delivery);

        // Simulate failed() lifecycle method triggered by Laravel when retries are exhausted
        $exception = new Exception('Max retries exceeded');
        
        $job->failed($exception);

        // We assert that the database delivery record is correctly marked as failed 
        // with the exception message in the response body.
        $this->assertDatabaseHas('reseller_callbacks', [
            'id' => $delivery->id,
            'status' => 'failed',
        ]);
        
        $delivery->refresh();
        $this->assertStringContainsString('Max retries exceeded', $delivery->response_body);
    }
}
