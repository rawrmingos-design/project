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

    public function test_retired_provider_status_schedules_are_paused(): void
    {
        $descriptions = collect(app(Schedule::class)->events())
            ->pluck('description')
            ->filter()
            ->values();

        foreach (['gameshop', 'strleyashop', 'elitedias', 'yezzpay'] as $provider) {
            $this->assertNotContains('provider-order-status:' . $provider, $descriptions);
        }
    }

    public function test_retained_digiflazz_sync_schedule_remains_enabled(): void
    {
        $event = collect(app(Schedule::class)->events())->first(
            fn ($event): bool => str_contains($event->description ?? '', 'App\\Jobs\\DigiflazzSyncJob'),
        );

        $this->assertNotNull($event, 'Missing retained Digiflazz sync schedule.');
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->onOneServer);
        $this->assertSame('0 * * * *', $event->expression);
    }
}
