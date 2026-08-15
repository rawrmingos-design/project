<?php

namespace Tests\Feature\Bot;

use App\Models\InboundSourcePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BotWebhookSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['services.telegram-bot-api.webhook_secret' => 'correct-telegram-secret']);

        // Setup trusted IP range policy
        $telegramPolicy = InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'telegram',
            'mode' => 'enforce',
            'is_active' => true,
        ]);
        $telegramPolicy->entries()->create([
            'value_type' => 'cidr_ipv4',
            'value' => '149.154.160.0/20',
            'is_active' => true,
        ]);

        $fonntePolicy = InboundSourcePolicy::query()->create([
            'source_domain' => 'bot_webhook',
            'source_name' => 'fonnte',
            'mode' => 'enforce',
            'is_active' => true,
        ]);
        $fonntePolicy->entries()->create([
            'value_type' => 'ipv4',
            'value' => '202.162.212.1',
            'is_active' => true,
        ]);
    }

    public function test_rejects_unauthorized_ip()
    {
        // IP not in whitelist
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->postJson('/api/webhooks/bot/telegram', []);
            
        $response->assertStatus(403);
    }

    public function test_rejects_invalid_secret_token()
    {
        // Valid IP, but invalid secret
        $response = $this->withServerVariables(['REMOTE_ADDR' => '149.154.160.10'])
            ->postJson('/api/webhooks/bot/telegram', [], [
                'X-Telegram-Bot-Api-Secret-Token' => 'wrong-secret',
            ]);
            
        $response->assertStatus(401);
    }

    public function test_accepts_valid_ip_and_secret()
    {
        $response = $this->withServerVariables(['REMOTE_ADDR' => '149.154.160.10'])
            ->postJson('/api/webhooks/bot/telegram', [], [
                'X-Telegram-Bot-Api-Secret-Token' => 'correct-telegram-secret',
            ]);
            
        // 200 OK because the telegram adapter returns status: ignored on empty payload
        $response->assertStatus(200); 
    }

    public function test_fonnte_rejects_unauthorized_ip()
    {
        // IP not in whitelist
        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->postJson('/api/webhooks/bot/fonnte', []);
            
        $response->assertStatus(403);
    }

    public function test_fonnte_accepts_allowed_ip_without_authorization(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '202.162.212.1'])
            ->postJson('/api/webhooks/bot/fonnte', [])
            ->assertOk();
    }

    public function test_fonnte_ignores_inbound_authorization_header(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '202.162.212.1'])
            ->postJson('/api/webhooks/bot/fonnte', [], [
                'Authorization' => 'arbitrary-inbound-value',
            ])
            ->assertOk();
    }

    public function test_fonnte_ignores_inbound_device_token_payload(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '202.162.212.1'])
            ->postJson('/api/webhooks/bot/fonnte', [
                'device_token' => 'arbitrary-inbound-value',
            ])
            ->assertOk();
    }

    public function test_fonnte_inbound_acceptance_does_not_depend_on_configured_outbound_token(): void
    {
        config(['services.fonnte.device_token' => '']);

        $this->withServerVariables(['REMOTE_ADDR' => '202.162.212.1'])
            ->postJson('/api/webhooks/bot/fonnte', [], [
                'Authorization' => 'arbitrary-inbound-value',
            ])
            ->assertOk();
    }
}
