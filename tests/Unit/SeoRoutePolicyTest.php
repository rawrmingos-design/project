<?php

namespace Tests\Unit;

use App\Support\SeoRoutePolicy;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class SeoRoutePolicyTest extends TestCase
{
    public function test_private_routes_are_noindex(): void
    {
        foreach (['/id/sign-in', '/id/sign-up', '/id/dashboard', '/id/invoices/INV-1', '/id/search/products'] as $path) {
            $request = Request::create($path, 'GET');
            $this->assertSame(SeoRoutePolicy::PRIVATE_ROBOTS, SeoRoutePolicy::robots($request), $path);
        }
    }

    public function test_public_routes_are_indexable(): void
    {
        foreach (['/id', '/id/artikel', '/id/calculator/winrate', '/id/leaderboard'] as $path) {
            $request = Request::create($path, 'GET');
            $this->assertSame(SeoRoutePolicy::INDEXABLE_ROBOTS, SeoRoutePolicy::robots($request), $path);
        }
    }
}
