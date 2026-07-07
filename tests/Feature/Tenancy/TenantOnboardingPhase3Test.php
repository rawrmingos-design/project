<?php

namespace Tests\Feature\Tenancy;

use App\Models\Pembelian;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\TenantRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingPhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_subdomain_check_reports_available_reserved_and_taken(): void
    {
        Tenant::query()->create([
            'name' => 'Taken Store',
            'subdomain' => 'taken-store',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/subdomain/check?name=fresh-store')
            ->assertOk()
            ->assertJson(['available' => true, 'subdomain' => 'fresh-store']);

        $this->getJson('/api/subdomain/check?name=admin')
            ->assertOk()
            ->assertJson(['available' => false, 'subdomain' => 'admin']);

        $this->getJson('/api/subdomain/check?name=taken-store')
            ->assertOk()
            ->assertJson(['available' => false, 'subdomain' => 'taken-store']);
    }

    public function test_tenant_register_creates_owner_pending_tenant_subscription_and_invoice(): void
    {
        $response = $this->postJson('/api/tenant/register', [
            'name' => 'Raka Reseller',
            'email' => 'raka@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Raka Topup',
            'subdomain' => 'raka-topup',
            'tier' => 'starter',
        ]);

        $response->assertCreated()
            ->assertJsonPath('tenant.subdomain', 'raka-topup')
            ->assertJsonPath('tenant.status', Tenant::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('invoice.amount', 500000)
            ->assertJsonPath('invoice.status', SubscriptionInvoice::STATUS_PENDING);

        $this->assertDatabaseHas('users', [
            'email' => 'raka@example.test',
            'role' => 'Member',
        ]);
        $tenant = Tenant::query()->where('subdomain', 'raka-topup')->firstOrFail();
        $this->assertDatabaseHas('tenants', [
            'subdomain' => 'raka-topup',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'raka@example.test',
            'tenant_id' => $tenant->id,
        ]);
        $this->assertDatabaseHas('subscriptions', [
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_PENDING,
        ]);
    }

    public function test_subscription_webhook_requires_token_and_activates_invoice_idempotently(): void
    {
        config(['services.tenant_subscription.webhook_token' => 'secret-token']);

        $owner = User::factory()->create(['role' => 'Member']);
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Pending Store',
            'subdomain' => 'pending-store',
            'tier' => 'starter',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_PENDING,
        ]);
        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'manual',
            'gateway_ref' => 'SUB-TEST-001',
            'due_date' => now()->addDay(),
        ]);

        $this->postJson('/api/webhooks/subscription', [
            'invoice_id' => $invoice->id,
            'status' => 'paid',
        ])->assertUnauthorized();

        $this->withToken('secret-token')
            ->postJson('/api/webhooks/subscription', [
                'status' => 'paid',
            ])
            ->assertUnprocessable();

        $this->withToken('secret-token')
            ->postJson('/api/webhooks/subscription', [
                'invoice_id' => $invoice->id,
                'gateway_ref' => 'SUB-OTHER-001',
                'status' => 'paid',
            ])
            ->assertNotFound();

        $payload = [
            'invoice_id' => $invoice->id,
            'gateway_ref' => 'SUB-TEST-001',
            'status' => 'paid',
        ];

        $this->withToken('secret-token')
            ->postJson('/api/webhooks/subscription', $payload)
            ->assertOk()
            ->assertJsonPath('tenant_status', Tenant::STATUS_ACTIVE)
            ->assertJsonPath('subscription_status', Subscription::STATUS_ACTIVE);

        $this->withToken('secret-token')
            ->postJson('/api/webhooks/subscription', $payload)
            ->assertOk()
            ->assertJsonPath('tenant_status', Tenant::STATUS_ACTIVE);

        $this->assertSame('Gold', $owner->fresh()->role);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_tenant_owner_can_view_dashboard_and_update_settings(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $owner = User::factory()->create([
            'role' => 'Gold',
            'balance' => 25000,
        ]);
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Owner Store',
            'subdomain' => 'owner-store',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        Pembelian::factory()->create([
            'tenant_id' => $tenant->id,
            'harga' => 12000,
            'profit' => 2000,
            'status' => 'Success',
        ]);

        $this->actingAs($owner)
            ->get('https://owner-store.topupengine.test/dashboard')
            ->assertOk()
            ->assertSee('Owner Store')
            ->assertSee('Saldo Komisi');

        $this->actingAs($owner)
            ->post('https://owner-store.topupengine.test/settings', [
                'name' => 'Owner Store Updated',
                'primary_color' => '#111111',
                'accent_color' => '#222222',
                'contact_whatsapp' => '6281234567890',
                'markup_type' => 'fixed',
                'markup_value' => 1500,
            ])
            ->assertRedirect(route('tenant.settings'));

        $tenant->refresh();
        $this->assertSame('Owner Store Updated', $tenant->name);
        $this->assertSame('fixed', $tenant->margin_config['markup_type']);
        $this->assertSame(1500.0, $tenant->margin_config['markup_value']);
        $this->assertSame('#111111', $tenant->theme['primary_color']);
    }
}
