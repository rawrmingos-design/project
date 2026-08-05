<?php

namespace Tests\Feature;

use App\Http\Middleware\CaptureTiktokClickId;
use App\Models\Tenant;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CaptureTiktokClickIdTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', CaptureTiktokClickId::class])
            ->get('/test-tiktok-click-id', fn () => 'OK');
    }

    public function test_ttclid_query_parameter_is_queued_for_twenty_eight_days(): void
    {
        $response = $this->get('/test-tiktok-click-id?ttclid=CLICK-123');

        $response->assertOk();
        $response->assertCookie('ttclid', 'CLICK-123');
    }

    public function test_missing_ttclid_does_not_queue_cookie(): void
    {
        $response = $this->get('/test-tiktok-click-id');

        $response->assertOk();
        $response->assertCookieMissing('ttclid');
    }

    public function test_tenant_storefront_does_not_capture_main_pixel_ttclid(): void
    {
        $tenant = new Tenant([
            'name' => 'Tenant TikTok',
            'subdomain' => 'tenant-tiktok-click',
            'status' => 'active',
        ]);
        $tenant->setAttribute('id', 999);
        app(TenantContext::class)->set($tenant);

        try {
            $response = $this->get('/test-tiktok-click-id?ttclid=TENANT-CLICK');
        } finally {
            app(TenantContext::class)->clear();
        }

        $response->assertOk();
        $response->assertCookieMissing('ttclid');
    }
}
