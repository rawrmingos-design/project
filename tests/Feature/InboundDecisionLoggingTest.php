<?php

namespace Tests\Feature;

use App\Models\InboundSourceEvent;
use App\Models\InboundSourcePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundDecisionLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_whitelist_decision_is_persisted_to_database(): void
    {
        InboundSourcePolicy::query()->create([
            'source_domain' => 'payment_gateway',
            'source_name' => 'tripay',
            'mode' => 'enforce',
            'is_active' => true,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/wejizy/tripay/callback', [
                'reference' => 'TRIPAY-LOG-001',
                'status' => 'PAID',
            ])
            ->assertStatus(403);

        $event = InboundSourceEvent::query()->latest('id')->first();

        $this->assertNotNull($event);
        $this->assertSame('payment_gateway', $event->source_domain);
        $this->assertSame('tripay', $event->source_name);
        $this->assertSame('enforce', $event->mode);
        $this->assertSame('deny', $event->decision);
        $this->assertSame('no_entry_match', $event->reason);
    }
}
