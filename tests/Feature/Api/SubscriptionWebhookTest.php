<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_subscription_webhook_rejects_unconfigured_token_with_401(): void
    {
        // Enforce unset token
        config(['services.tenant_subscription.webhook_token' => '']);
        putenv('TENANT_SUBSCRIPTION_WEBHOOK_TOKEN=');

        $response = $this->postJson(route('api.webhooks.subscription'), [
            'invoice_id' => 1,
            'status' => 'paid',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Webhook token not configured.',
            ]);
    }

    public function test_manual_subscription_webhook_rejects_invalid_token_with_401(): void
    {
        config(['services.tenant_subscription.webhook_token' => 'secret-token']);

        $response = $this->withToken('wrong-token')
            ->postJson(route('api.webhooks.subscription'), [
                'invoice_id' => 1,
                'status' => 'paid',
            ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Unauthorized webhook.',
            ]);
    }
}
