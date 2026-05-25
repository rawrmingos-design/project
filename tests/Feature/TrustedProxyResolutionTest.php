<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyResolutionTest extends TestCase
{
    public function test_forwarded_ip_is_ignored_when_proxy_is_not_trusted(): void
    {
        config([
            'trustedproxy.proxies' => null,
            'trustedproxy.headers' => 'forwarded_for,forwarded_host,forwarded_port,forwarded_proto,aws_elb',
        ]);

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ])->get('/wip')
            ->assertOk()
            ->assertJson(['ip' => '127.0.0.1']);
    }

    public function test_forwarded_ip_is_used_when_proxy_is_explicitly_trusted(): void
    {
        config([
            'trustedproxy.proxies' => '127.0.0.1',
            'trustedproxy.headers' => 'forwarded_for,forwarded_host,forwarded_port,forwarded_proto,aws_elb',
        ]);

        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
        ])->get('/wip')
            ->assertOk()
            ->assertJson(['ip' => '203.0.113.10']);
    }
}
