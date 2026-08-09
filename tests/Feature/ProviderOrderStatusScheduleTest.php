<?php

namespace Tests\Feature;

use App\Jobs\SyncProviderOrderStatusesJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Tests\TestCase;

class ProviderOrderStatusScheduleTest extends TestCase
{
    public function test_provider_status_jobs_have_stable_per_provider_unique_keys(): void
    {
        $gameshop = new SyncProviderOrderStatusesJob(' GameShop ');
        $yezzpay = new SyncProviderOrderStatusesJob('yezzpay');

        $this->assertInstanceOf(ShouldBeUnique::class, $gameshop);
        $this->assertSame('provider-order-status-sync:gameshop', $gameshop->uniqueId());
        $this->assertSame('provider-order-status-sync:yezzpay', $yezzpay->uniqueId());
        $this->assertSame(300, $gameshop->uniqueFor);
    }

    public function test_all_provider_status_schedules_prevent_overlap_and_run_on_one_server(): void
    {
        $events = collect(app(Schedule::class)->events());

        foreach (['gameshop', 'strleyashop', 'elitedias', 'yezzpay'] as $provider) {
            $event = $events->first(
                fn ($event): bool => $event->description === 'provider-order-status:' . $provider,
            );

            $this->assertNotNull($event, 'Missing schedule for ' . $provider);
            $this->assertTrue($event->withoutOverlapping);
            $this->assertTrue($event->onOneServer);
            $this->assertSame('*/5 * * * *', $event->expression);
        }
    }
}
