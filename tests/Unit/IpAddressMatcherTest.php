<?php

namespace Tests\Unit;

use App\Support\IpAddressMatcher;
use PHPUnit\Framework\TestCase;

class IpAddressMatcherTest extends TestCase
{
    public function test_it_matches_exact_ipv4_addresses(): void
    {
        $this->assertTrue(IpAddressMatcher::matches('203.0.113.10', '203.0.113.10'));
        $this->assertFalse(IpAddressMatcher::matches('203.0.113.10', '203.0.113.11'));
    }

    public function test_it_normalizes_and_matches_ipv6_addresses(): void
    {
        $expanded = '2001:0db8:0000:0000:0000:ff00:0042:8329';
        $compressed = '2001:db8::ff00:42:8329';

        $this->assertSame($compressed, IpAddressMatcher::normalize($expanded));
        $this->assertTrue(IpAddressMatcher::matches($expanded, $compressed));
    }

    public function test_it_matches_ipv4_cidr_ranges(): void
    {
        $this->assertTrue(IpAddressMatcher::matches('203.0.113.25', '203.0.113.0/24'));
        $this->assertFalse(IpAddressMatcher::matches('203.0.114.25', '203.0.113.0/24'));
    }

    public function test_it_matches_ipv6_cidr_ranges(): void
    {
        $this->assertTrue(IpAddressMatcher::matches('2001:db8::1', '2001:db8::/32'));
        $this->assertFalse(IpAddressMatcher::matches('2001:db9::1', '2001:db8::/32'));
    }
}
