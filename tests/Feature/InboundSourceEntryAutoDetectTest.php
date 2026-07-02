<?php

namespace Tests\Feature;

use App\Models\InboundSourceEntry;
use App\Models\InboundSourcePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundSourceEntryAutoDetectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_ip_value_type_from_value(): void
    {
        $this->assertSame('ipv4', InboundSourceEntry::detectValueType('203.0.113.10'));
        $this->assertSame('cidr_ipv4', InboundSourceEntry::detectValueType('203.0.113.0/24'));
        $this->assertSame('ipv6', InboundSourceEntry::detectValueType('2001:db8::1'));
        $this->assertSame('cidr_ipv6', InboundSourceEntry::detectValueType('2001:db8::/32'));
        $this->assertNull(InboundSourceEntry::detectValueType('not-an-ip'));
        $this->assertNull(InboundSourceEntry::detectValueType('203.0.113.0/99'));

        $this->assertTrue(InboundSourceEntry::isValidValue('203.0.113.10'));
        $this->assertTrue(InboundSourceEntry::isValidValue('203.0.113.0/24'));
        $this->assertFalse(InboundSourceEntry::isValidValue('not-an-ip'));
        $this->assertFalse(InboundSourceEntry::isValidValue('203.0.113.0/99'));
    }

    public function test_it_persists_detected_value_type_on_save(): void
    {
        $policy = InboundSourcePolicy::query()->create([
            'source_domain' => 'payment_gateway',
            'source_name' => 'tripay',
            'mode' => 'log_only',
            'is_active' => true,
        ]);

        $entry = InboundSourceEntry::query()->create([
            'policy_id' => $policy->id,
            'value' => '203.0.113.0/24',
            'value_type' => 'ipv4',
            'is_active' => true,
        ]);

        $this->assertSame('cidr_ipv4', $entry->fresh()->value_type);
    }
}
