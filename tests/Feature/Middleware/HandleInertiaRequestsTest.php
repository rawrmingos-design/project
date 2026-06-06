<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_shares_favicon_from_site_config(): void
    {
        // Mock the SettingWeb data
        SettingWeb::factory()->create([
            'id' => 1,
            'logo_favicon' => '/assets/custom_favicon.png',
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/id/dashboard');

        $response->assertStatus(200);
        
        $response->assertInertia(fn (Assert $page) =>
            $page->has('siteConfig.favicon')
                 ->where('siteConfig.favicon', '/assets/custom_favicon.png')
        );
    }
}
