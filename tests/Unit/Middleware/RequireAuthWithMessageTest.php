<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RequireAuthWithMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RequireAuthWithMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_redirects_guest_to_login_with_default_message()
    {
        $middleware = new RequireAuthWithMessage();
        $request = Request::create('/test', 'GET');
        
        $response = $middleware->handle($request, function () {
            return response('OK');
        });
        
        $this->assertEquals(302, $response->getStatusCode());

        // Redirect kini membawa `?redirect=<path>` supaya user kembali ke halaman
        // yang dituju setelah login, jadi bandingkan path-nya, bukan URL persis.
        $target = (string) $response->headers->get('Location');
        $this->assertStringStartsWith(route('login'), $target);
        $this->assertEquals('Silakan login terlebih dahulu untuk mengakses halaman ini.', session('warning'));
    }
    
    public function test_redirects_guest_with_custom_message()
    {
        $middleware = new RequireAuthWithMessage();
        $request = Request::create('/test', 'GET');
        $customMessage = 'Custom auth message';
        
        $response = $middleware->handle($request, function () {
            return response('OK');
        }, $customMessage);
        
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($customMessage, session('warning'));
    }
    
    public function test_allows_authenticated_user()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);
        
        $middleware = new RequireAuthWithMessage();
        $request = Request::create('/test', 'GET');
        
        $response = $middleware->handle($request, function () {
            return response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }
}
