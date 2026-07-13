<?php

namespace Tests\Feature\Tenancy;

use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceEvent;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\DuitkuPopClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);
    }

    public function test_reseller_registration_page_renders_self_service_form(): void
    {
        config(['app.url' => 'https://topupengine.test']);

        $this->get('https://topupengine.test/id/reseller-topup')
            ->assertOk()
            ->assertSee('Buat website topup sendiri')
            ->assertSee('Kemitraan')
            ->assertSee('/api/tenant/register')
            ->assertSee('/api/subdomain/check?name=')
            ->assertSee('name="terms_accepted"', false)
            ->assertSee('Starter')
            ->assertSee('Business')
            ->assertSee('Hubungi admin');
    }

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
        $this->fakeDuitkuInvoice();

        $response = $this->postJson('/api/tenant/register', [
            'name' => 'Raka Reseller',
            'email' => 'raka@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Raka Topup',
            'subdomain' => 'raka-topup',
            'tier' => 'starter',
            'terms_accepted' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('tenant.subdomain', 'raka-topup')
            ->assertJsonPath('tenant.status', Tenant::STATUS_PENDING_PAYMENT)
            ->assertJsonPath('invoice.amount', 500000)
            ->assertJsonPath('invoice.status', SubscriptionInvoice::STATUS_PENDING)
            ->assertJsonPath('invoice.gateway', 'duitku')
            ->assertJsonPath('invoice.payment_url', 'https://sandbox.duitku.test/pay/1')
            ->assertJsonPath('invoice.duitku_reference', 'DUITKU-REF-001');

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
            'gateway_ref' => $tenant->subscriptions()->firstOrFail()->gateway_ref,
        ]);
        $invoice = SubscriptionInvoice::query()->where('gateway_ref', $tenant->subscriptions()->firstOrFail()->gateway_ref)->firstOrFail();
        $this->assertSame('duitku', $invoice->gateway);
        $this->assertSame('DUITKU-REF-001', data_get($invoice->metadata, 'duitku.reference'));
        $this->assertSame('https://sandbox.duitku.test/pay/1', data_get($invoice->metadata, 'duitku.payment_url'));
    }

    public function test_tenant_register_requires_terms_and_self_service_tier(): void
    {
        $this->fakeDuitkuInvoice();

        $this->postJson('/api/tenant/register', [
            'name' => 'No Terms',
            'email' => 'no-terms@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'No Terms Store',
            'subdomain' => 'no-terms-store',
            'tier' => 'starter',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('terms_accepted');

        $this->postJson('/api/tenant/register', [
            'name' => 'Enterprise Owner',
            'email' => 'enterprise@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Enterprise Store',
            'subdomain' => 'enterprise-store',
            'tier' => 'enterprise',
            'terms_accepted' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('tier');

        $this->assertDatabaseCount('tenants', 0);
        $this->assertDatabaseMissing('users', ['email' => 'no-terms@example.test']);
        $this->assertDatabaseMissing('users', ['email' => 'enterprise@example.test']);
    }

    public function test_tenant_register_rolls_back_when_duitku_invoice_creation_fails(): void
    {
        $this->createDuitkuSettings();

        $this->app->instance(DuitkuPopClient::class, new class extends DuitkuPopClient {
            public function createInvoice(array $params, \Duitku\Config $config): array
            {
                throw new \RuntimeException('Duitku unavailable.');
            }
        });

        $this->postJson('/api/tenant/register', [
            'name' => 'Failed Gateway',
            'email' => 'failed-gateway@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Failed Gateway Store',
            'subdomain' => 'failed-gateway-store',
            'tier' => 'starter',
            'terms_accepted' => true,
        ])->assertStatus(502);

        $this->assertDatabaseMissing('users', ['email' => 'failed-gateway@example.test']);
        $this->assertDatabaseMissing('tenants', ['subdomain' => 'failed-gateway-store']);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseCount('subscription_invoices', 0);
    }

    public function test_duitku_invoice_can_be_refreshed_for_retry(): void
    {
        $this->createDuitkuSettings();
        [$owner, $tenant, $subscription, $invoice] = $this->createPendingDuitkuSubscription();

        $this->app->instance(DuitkuPopClient::class, new class extends DuitkuPopClient {
            public function createInvoice(array $params, \Duitku\Config $config): array
            {
                return [
                    'statusCode' => '00',
                    'statusMessage' => 'SUCCESS',
                    'reference' => 'DUITKU-REF-RETRY',
                    'paymentUrl' => 'https://sandbox.duitku.test/pay/retry',
                    'amount' => $params['paymentAmount'],
                ];
            }
        });

        $refreshed = app(\App\Tenancy\DuitkuSubscriptionPaymentService::class)->createAndStoreInvoice($invoice, [
            'duitku' => [
                'retry_count' => 1,
                'retried_at' => now()->toIso8601String(),
            ],
        ]);

        $this->assertSame(SubscriptionInvoice::STATUS_PENDING, $refreshed->status);
        $this->assertSame('DUITKU-REF-RETRY', data_get($refreshed->metadata, 'duitku.reference'));
        $this->assertSame('https://sandbox.duitku.test/pay/retry', data_get($refreshed->metadata, 'duitku.payment_url'));
        $this->assertSame(1, data_get($refreshed->metadata, 'duitku.retry_count'));
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
        $this->assertDatabaseCount('subscription_invoice_events', 2);
        $this->assertDatabaseHas('subscription_invoice_events', [
            'subscription_invoice_id' => $invoice->id,
            'type' => SubscriptionInvoiceEvent::TYPE_CALLBACK,
            'gateway' => 'manual',
            'status' => SubscriptionInvoice::STATUS_PAID,
            'reference' => 'SUB-TEST-001',
        ]);
    }

    public function test_duitku_subscription_callback_is_excluded_from_csrf_verification(): void
    {
        $middleware = new \App\Http\Middleware\VerifyCsrfToken(app(), app('encrypter'));
        $property = new \ReflectionProperty($middleware, 'except');
        $property->setAccessible(true);

        $this->assertContains('wejizy/duitku/subscription/callback', $property->getValue($middleware));
    }

    public function test_duitku_subscription_callback_valid_signature_activates_tenant_idempotently(): void
    {
        $settings = $this->createDuitkuSettings();
        [$owner, $tenant, $subscription, $invoice] = $this->createPendingDuitkuSubscription();
        $payload = $this->duitkuPayload($settings, $invoice);

        $this->post('/wejizy/duitku/subscription/callback', $payload)
            ->assertOk()
            ->assertSee('SUCCESS');

        $this->post('/wejizy/duitku/subscription/callback', $payload)
            ->assertOk()
            ->assertSee('SUCCESS');

        $this->assertSame('Gold', $owner->fresh()->role);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame('00', data_get($invoice->fresh()->metadata, 'duitku.last_callback.result_code'));
        $this->assertDatabaseCount('subscription_invoice_events', 2);
        $this->assertDatabaseHas('subscription_invoice_events', [
            'subscription_invoice_id' => $invoice->id,
            'type' => SubscriptionInvoiceEvent::TYPE_CALLBACK,
            'gateway' => 'duitku',
            'status' => '00',
            'reference' => 'DUITKU-REF-001',
        ]);
    }

    public function test_tenant_registration_dispatches_invoice_notification(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendTenantNotificationJob::class]);
        $this->fakeDuitkuInvoice();

        $this->postJson('/api/tenant/register', [
            'name' => 'Notify Owner',
            'email' => 'notify@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Notify Store',
            'subdomain' => 'notify-store',
            'tier' => 'starter',
            'terms_accepted' => true,
        ])->assertCreated();

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendTenantNotificationJob::class, function ($job) {
            return $job->event === \App\Jobs\SendTenantNotificationJob::EVENT_REGISTRATION_INVOICE;
        });
    }

    public function test_tenant_activation_dispatches_notification_idempotently(): void
    {
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendTenantNotificationJob::class]);
        $settings = $this->createDuitkuSettings();
        [$owner, $tenant, $subscription, $invoice] = $this->createPendingDuitkuSubscription();

        $this->post('/wejizy/duitku/subscription/callback', $this->duitkuPayload($settings, $invoice))
            ->assertOk();

        \Illuminate\Support\Facades\Bus::assertDispatched(\App\Jobs\SendTenantNotificationJob::class, function ($job) use ($invoice) {
            return $job->event === \App\Jobs\SendTenantNotificationJob::EVENT_ACTIVATED && $job->invoiceId === $invoice->id;
        });

        // Clear fake queue memory
        \Illuminate\Support\Facades\Bus::fake([\App\Jobs\SendTenantNotificationJob::class]);

        $this->post('/wejizy/duitku/subscription/callback', $this->duitkuPayload($settings, $invoice))
            ->assertOk();

        \Illuminate\Support\Facades\Bus::assertNotDispatched(\App\Jobs\SendTenantNotificationJob::class);
    }

    public function test_duitku_subscription_callback_invalid_signature_is_rejected(): void
    {
        $settings = $this->createDuitkuSettings();
        [$owner, $tenant, $subscription, $invoice] = $this->createPendingDuitkuSubscription();
        $payload = $this->duitkuPayload($settings, $invoice, ['signature' => 'bad-signature']);

        $this->post('/wejizy/duitku/subscription/callback', $payload)
            ->assertStatus(400)
            ->assertSee('Invalid signature');

        $this->assertSame('Member', $owner->fresh()->role);
        $this->assertSame(Tenant::STATUS_PENDING_PAYMENT, $tenant->fresh()->status);
        $this->assertSame(Subscription::STATUS_PENDING, $subscription->fresh()->status);
        $this->assertSame(SubscriptionInvoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_duitku_subscription_callback_rejects_invalid_merchant_amount_and_reference(): void
    {
        $settings = $this->createDuitkuSettings();
        [$owner, $tenant, $subscription, $invoice] = $this->createPendingDuitkuSubscription();

        $invalidMerchant = $this->duitkuPayload($settings, $invoice, ['merchantCode' => 'BADMERCHANT']);
        $invalidMerchant['signature'] = md5($invalidMerchant['merchantCode'] . $invalidMerchant['amount'] . $invalidMerchant['merchantOrderId'] . $settings->duitku_merchant_key);

        $this->post('/wejizy/duitku/subscription/callback', $invalidMerchant)
            ->assertStatus(400)
            ->assertSee('Invalid merchant');

        $invalidAmount = $this->duitkuPayload($settings, $invoice, ['amount' => '999999']);
        $this->post('/wejizy/duitku/subscription/callback', $invalidAmount)
            ->assertStatus(400)
            ->assertSee('Invalid amount');

        $invalidReference = $this->duitkuPayload($settings, $invoice, ['reference' => 'OTHER-REF']);
        $this->post('/wejizy/duitku/subscription/callback', $invalidReference)
            ->assertStatus(400)
            ->assertSee('Invalid reference');

        $this->assertSame('Member', $owner->fresh()->role);
        $this->assertSame(Tenant::STATUS_PENDING_PAYMENT, $tenant->fresh()->status);
        $this->assertSame(Subscription::STATUS_PENDING, $subscription->fresh()->status);
        $this->assertSame(SubscriptionInvoice::STATUS_PENDING, $invoice->fresh()->status);
    }

    public function test_duitku_subscription_callback_failed_result_expires_invoice_without_activation(): void
    {
        $settings = $this->createDuitkuSettings();
        [$owner, $tenant, $subscription, $invoice] = $this->createPendingDuitkuSubscription();
        $payload = $this->duitkuPayload($settings, $invoice, ['resultCode' => '01']);

        $this->post('/wejizy/duitku/subscription/callback', $payload)
            ->assertOk()
            ->assertSee('SUCCESS');

        $this->assertSame('Member', $owner->fresh()->role);
        $this->assertSame(Tenant::STATUS_PENDING_PAYMENT, $tenant->fresh()->status);
        $this->assertSame(Subscription::STATUS_PENDING, $subscription->fresh()->status);
        $this->assertSame(SubscriptionInvoice::STATUS_EXPIRED, $invoice->fresh()->status);
    }

    public function test_duitku_subscription_failed_callback_after_paid_does_not_downgrade_activation(): void
    {
        $settings = $this->createDuitkuSettings();
        [$owner, $tenant, $subscription, $invoice] = $this->createPendingDuitkuSubscription();

        $this->post('/wejizy/duitku/subscription/callback', $this->duitkuPayload($settings, $invoice))
            ->assertOk()
            ->assertSee('SUCCESS');

        $this->post('/wejizy/duitku/subscription/callback', $this->duitkuPayload($settings, $invoice, ['resultCode' => '01']))
            ->assertOk()
            ->assertSee('SUCCESS');

        $this->assertSame('Gold', $owner->fresh()->role);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame('01', data_get($invoice->fresh()->metadata, 'duitku.last_callback.result_code'));
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

    private function fakeDuitkuInvoice(): SettingWeb
    {
        $settings = $this->createDuitkuSettings();

        $this->app->instance(DuitkuPopClient::class, new class extends DuitkuPopClient {
            public function createInvoice(array $params, \Duitku\Config $config): array
            {
                return [
                    'statusCode' => '00',
                    'statusMessage' => 'SUCCESS',
                    'reference' => 'DUITKU-REF-001',
                    'paymentUrl' => 'https://sandbox.duitku.test/pay/1',
                    'amount' => $params['paymentAmount'],
                ];
            }
        });

        return $settings;
    }

    private function createDuitkuSettings(): SettingWeb
    {
        return SettingWeb::query()->firstOrCreate(['id' => 1], [
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'topupindo-test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'order_prefik' => 'INV',
            'paydisini_apikey' => 'paydisini-test-key',
            'tripay_api' => 'tripay-test-key',
            'tripay_merchant_code' => 'tripay-merchant-test',
            'tripay_private_key' => 'tripay-private-test',
            'duitku_merchant_code' => 'DTEST',
            'duitku_merchant_key' => 'duitku-secret-test',
            'duitku_mode' => 'sandbox',
            'vip_apiid' => 'vip-id',
            'vip_apikey' => 'vip-key',
        ]);
    }

    private function createPendingDuitkuSubscription(): array
    {
        $owner = User::factory()->create(['role' => 'Member']);
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Pending Duitku Store',
            'subdomain' => 'pending-duitku-store',
            'tier' => 'starter',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_PENDING,
            'gateway_ref' => 'SUB-DUITKU-001',
        ]);
        $invoice = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => 'SUB-DUITKU-001',
            'due_date' => now()->addDay(),
            'metadata' => [
                'source' => 'tenant_self_registration',
                'duitku' => [
                    'merchant_order_id' => 'SUB-DUITKU-001',
                    'reference' => 'DUITKU-REF-001',
                ],
            ],
        ]);

        return [$owner, $tenant, $subscription, $invoice];
    }

    private function duitkuPayload(SettingWeb $settings, SubscriptionInvoice $invoice, array $overrides = []): array
    {
        $payload = array_merge([
            'merchantCode' => $settings->duitku_merchant_code,
            'amount' => (string) $invoice->amount,
            'merchantOrderId' => $invoice->gateway_ref,
            'productDetail' => 'Langganan White-label starter',
            'resultCode' => '00',
            'reference' => 'DUITKU-REF-001',
        ], $overrides);

        if (! array_key_exists('signature', $overrides)) {
            $payload['signature'] = md5(
                $payload['merchantCode'] .
                $payload['amount'] .
                $payload['merchantOrderId'] .
                $settings->duitku_merchant_key
            );
        }

        return $payload;
    }
}
