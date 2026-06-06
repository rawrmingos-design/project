<?php

namespace Tests\Feature\Public\Reseller;

use App\Models\User;
use App\Models\ResellerIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SandboxControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createResellerUser(): User
    {
        return User::factory()->create([
            'role' => 'Member',
        ]);
    }

    private function setupSandboxIntegration(User $user): void
    {
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-SANDBOX-01',
            'is_active'        => true,
            'mode'             => 'sandbox',
            'callback_url'     => 'https://my-webhook.com/api',
            'api_key'          => 'hashed_sandbox_secret',
            'api_key_hint'     => 'sandbox_secret_hint',
        ]);
    }

    public function test_reseller_can_view_sandbox_page(): void
    {
        $user = $this->createResellerUser();
        $this->setupSandboxIntegration($user);

        $response = $this->actingAs($user)->get('/id/reseller/sandbox');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page->component('Public/Pages/Reseller/Sandbox'));
    }

    public function test_reseller_can_trigger_sandbox_delivery_successfully(): void
    {
        $user = $this->createResellerUser();
        $this->setupSandboxIntegration($user);

        // Fake HTTP response to simulate a successful 200 OK from the reseller's webhook server
        Http::fake([
            'https://my-webhook.com/api' => Http::response(['status' => 'ok'], 200),
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/sandbox/test-webhook');

        $response->assertRedirect('/id/reseller/sandbox');
        $response->assertSessionHas('success', 'Webhook Sandbox berhasil dikirim dan direspon dengan HTTP 200!');
        
        $this->assertDatabaseHas('reseller_callbacks', [
            'user_id' => $user->id,
            'event'   => 'sandbox.ping',
            'status'  => 'delivered',
            'response_code' => 200,
        ]);
    }

    public function test_reseller_sandbox_delivery_handles_failed_response(): void
    {
        $user = $this->createResellerUser();
        $this->setupSandboxIntegration($user);

        // Fake HTTP response to simulate a failure (e.g. 500 Internal Server Error)
        Http::fake([
            'https://my-webhook.com/api' => Http::response(['error' => 'internal error'], 500),
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/sandbox/test-webhook');

        $response->assertRedirect('/id/reseller/sandbox');
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Webhook Sandbox dikirim, tapi server Anda merespon dengan error HTTP 500', session('error'));

        $this->assertDatabaseHas('reseller_callbacks', [
            'user_id' => $user->id,
            'event'   => 'sandbox.ping',
            'status'  => 'failed',
            'response_code' => 500,
        ]);
    }

    public function test_reseller_cannot_trigger_sandbox_without_url(): void
    {
        $user = $this->createResellerUser();
        
        // Setup integration but without callback_url
        ResellerIntegration::create([
            'user_id'          => $user->id,
            'integration_code' => 'TEST-SANDBOX-02',
            'is_active'        => true,
            'mode'             => 'sandbox',
            'callback_url'     => null, // Missing URL
        ]);

        $response = $this->actingAs($user)->post('/id/reseller/sandbox/test-webhook');

        $response->assertRedirect();
        $response->assertSessionHas('error', 'URL Webhook Sandbox belum diatur. Silakan isi URL terlebih dahulu.');
    }
}
