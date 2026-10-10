<?php

namespace Tests\Feature\Tenancy;

use App\Jobs\SendTenantNotificationJob;
use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\Payments\DuitkuPopClient;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Notifikasi billing berulang (B6): invoice terbit, peringatan saat tenggang,
 * dan pemberitahuan suspend — masing-masing TEPAT SEKALI, walau command
 * dijalankan berkali-kali.
 */
class BillingReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenancy.disabled' => false,
            'billing.renewal_enabled' => true,
            'billing.reminder_days_before' => 3,
            'billing.grace_days' => 3,
            'billing.late_fee' => 10000,
        ]);

        $this->fakeDuitku();
    }

    public function test_invoice_perpanjangan_memicu_notifikasi_sekali_saja(): void
    {
        Bus::fake([SendTenantNotificationJob::class]);
        [, $subscription] = $this->activeSubscription(now()->addDays(2));

        $this->artisan('billing:renew-subscriptions', ['--apply' => true])->assertExitCode(0);
        $this->artisan('billing:renew-subscriptions', ['--apply' => true])->assertExitCode(0);
        $this->artisan('billing:renew-subscriptions', ['--apply' => true])->assertExitCode(0);

        Bus::assertDispatchedTimes(SendTenantNotificationJob::class, 1);
        Bus::assertDispatched(
            SendTenantNotificationJob::class,
            fn (SendTenantNotificationJob $job) => $job->event === SendTenantNotificationJob::EVENT_RENEWAL_INVOICE
        );
    }

    public function test_peringatan_tenggang_memicu_notifikasi_sekali_saja_dan_menyebut_denda(): void
    {
        Bus::fake([SendTenantNotificationJob::class]);
        [, $subscription] = $this->activeSubscription(now()->subDay());

        $this->artisan('billing:renew-subscriptions', ['--apply' => true])->assertExitCode(0);
        $this->artisan('billing:renew-subscriptions', ['--apply' => true])->assertExitCode(0);

        Bus::assertDispatchedTimes(SendTenantNotificationJob::class, 1);
        Bus::assertDispatched(
            SendTenantNotificationJob::class,
            fn (SendTenantNotificationJob $job) => $job->event === SendTenantNotificationJob::EVENT_PAYMENT_REMINDER
        );

        $invoice = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('metadata->kind', 'renewal')
            ->firstOrFail();

        $this->assertSame(510000, (int) $invoice->amount, 'Peringatan dikirim bersama tagihan berdenda.');
    }

    public function test_suspend_memicu_notifikasi_sekali_saja(): void
    {
        Bus::fake([SendTenantNotificationJob::class]);
        [, $subscription] = $this->activeSubscription(now()->subDays(10));

        $this->makePendingRenewal($subscription, 510000);

        $this->artisan('billing:suspend-overdue', ['--apply' => true])->assertExitCode(0);
        $this->artisan('billing:suspend-overdue', ['--apply' => true])->assertExitCode(0);

        Bus::assertDispatchedTimes(SendTenantNotificationJob::class, 1);
        Bus::assertDispatched(
            SendTenantNotificationJob::class,
            fn (SendTenantNotificationJob $job) => $job->event === SendTenantNotificationJob::EVENT_SUSPENDED
        );
    }

    // ---------------------------------------------------------------------
    // Isi pesan yang benar-benar dibaca customer
    // ---------------------------------------------------------------------

    public function test_pesan_perpanjangan_menyebut_periode_nominal_dan_tautan_bayar(): void
    {
        [, $subscription] = $this->activeSubscription(now()->addDays(2));
        $invoice = $this->makeRenewalInvoice($subscription, 500000, 0);

        [$email, $wa] = $this->renderNotification($invoice, SendTenantNotificationJob::EVENT_RENEWAL_INVOICE);

        $this->assertStringContainsString('Langganan', $email);
        $this->assertStringContainsString('perpanjangan', $email);
        $this->assertStringContainsString('https://sandbox.duitku.test/pay/renew', $email);
        $this->assertStringContainsString('Rp 500.000', $email);
        $this->assertStringNotContainsString('Denda', $email, 'Tagihan tanpa denda tidak boleh menyebut denda.');

        $this->assertStringContainsString('Perpanjang Langganan', $wa);
        $this->assertStringContainsString('Rp 500.000', $wa);
    }

    public function test_pesan_peringatan_menyebut_denda(): void
    {
        [, $subscription] = $this->activeSubscription(now()->subDay());
        $invoice = $this->makeRenewalInvoice($subscription, 510000, 10000);

        [$email, $wa] = $this->renderNotification($invoice, SendTenantNotificationJob::EVENT_PAYMENT_REMINDER);

        $this->assertStringContainsString('lewat jatuh tempo', $email);
        $this->assertStringContainsString('Rp 510.000', $email);
        $this->assertStringContainsString('Denda keterlambatan: Rp 10.000', $email);

        $this->assertStringContainsString('Denda keterlambatan: Rp 10.000', $wa);
        $this->assertStringContainsString('ditangguhkan sementara', $wa);
    }

    public function test_pesan_suspend_menyebut_data_masih_aman(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDays(10));
        $invoice = $this->makeRenewalInvoice($subscription, 510000, 10000);

        [$email, $wa] = $this->renderNotification($invoice, SendTenantNotificationJob::EVENT_SUSPENDED);

        $this->assertStringContainsString('ditangguhkan sementara', $email);
        $this->assertStringContainsString('Data dan nama domain kamu masih aman', $email);
        $this->assertStringContainsString('ditangguhkan', $wa);
        $this->assertStringContainsString('masih aman', $wa);
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /** @return array{0: Tenant, 1: Subscription} */
    private function activeSubscription(Carbon $periodEnd): array
    {
        $owner = User::factory()->create(['role' => 'Gold', 'email' => 'owner-' . uniqid() . '@example.test']);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Uji Notif',
            'subdomain' => 'toko-notif-' . uniqid(),
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-NOTIF-' . strtoupper(uniqid()),
            'current_period_start' => $periodEnd->copy()->subMonth(),
            'current_period_end' => $periodEnd,
        ]);

        return [$tenant, $subscription];
    }

    private function makeRenewalInvoice(Subscription $subscription, int $amount, int $lateFee): SubscriptionInvoice
    {
        return SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDay(),
            'metadata' => [
                'source' => 'billing_renewal',
                'kind' => 'renewal',
                'period_end' => $subscription->current_period_end?->toIso8601String(),
                'late_fee' => $lateFee,
                'duitku' => [
                    'reference' => 'DUITKU-NOTIF-REF',
                    'payment_url' => 'https://sandbox.duitku.test/pay/renew',
                ],
            ],
        ]);
    }

    private function makePendingRenewal(Subscription $subscription, int $amount): SubscriptionInvoice
    {
        return $this->makeRenewalInvoice($subscription, $amount, 10000);
    }

    /**
     * Jalankan job notifikasi dan tangkap isi email + pesan WhatsApp yang
     * benar-benar dikirim (bukan template di kode).
     *
     * @return array{0: string, 1: string}
     */
    private function renderNotification(SubscriptionInvoice $invoice, string $event): array
    {
        $email = new class extends EmailNotificationService
        {
            public array $sent = [];

            public function __construct() {}

            public function sendGenericEmail(string $to, string $subject, string $html, array $meta = []): bool
            {
                $this->sent[] = $html;

                return true;
            }
        };

        $wa = new class extends WhatsappNotificationService
        {
            public array $sent = [];

            public function __construct() {}

            public function sendNotification(string $target, string $templateSlug, array $payload = [], ?string $url = null, ?string $customToken = null): array
            {
                return ['success' => false];
            }

            public function sendMessage(string $target, string $message, ?string $url = null, ?string $customToken = null): array
            {
                $this->sent[] = $message;

                return ['success' => true];
            }
        };

        (new SendTenantNotificationJob($invoice->id, $event))->handle($email, $wa);

        $this->assertNotEmpty($email->sent, 'Email harus terkirim.');
        $this->assertNotEmpty($wa->sent, 'Pesan WhatsApp harus terkirim.');

        return [implode("\n", $email->sent), implode("\n", $wa->sent)];
    }

    private function fakeDuitku(): void
    {
        SettingWeb::query()->firstOrCreate(['id' => 1], [
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

        $this->app->instance(DuitkuPopClient::class, new class extends DuitkuPopClient
        {
            public function createInvoice(array $params, \Duitku\Config $config): array
            {
                return [
                    'statusCode' => '00',
                    'statusMessage' => 'SUCCESS',
                    'reference' => 'DUITKU-REF-' . substr(md5($params['merchantOrderId']), 0, 8),
                    'paymentUrl' => 'https://sandbox.duitku.test/pay/' . $params['merchantOrderId'],
                    'amount' => $params['paymentAmount'],
                ];
            }
        });
    }
}
