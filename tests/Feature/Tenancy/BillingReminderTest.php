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
use App\Tenancy\TenantProvisioningService;
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
        // Task 5: tautan mengarah ke halaman tagihan kita, bukan ke gateway.
        $this->assertStringContainsString('/tagihan/' . $invoice->ensurePublicToken(), $email);
        $this->assertStringContainsString('Rp 500.000', $email);
        $this->assertStringNotContainsString('Denda', $email, 'Tagihan tanpa denda tidak boleh menyebut denda.');

        $this->assertStringContainsString('Perpanjang Langganan', $wa);
        $this->assertStringContainsString('Rp 500.000', $wa);
        $this->assertStringContainsString('/tagihan/' . $invoice->ensurePublicToken(), $wa);
    }

    public function test_notifikasi_mengarah_ke_halaman_tagihan_bukan_gateway(): void
    {
        // Task 5: tautan Duitku mati dalam 1440 menit, tautan kita tidak. Kalau
        // tautan gateway yang dikirim, owner yang membaca email telat akan
        // menemukan tautan mati dan tidak punya jalan pulang.
        [, $subscription] = $this->activeSubscription(now()->subDay());
        $invoice = $this->makeRenewalInvoice($subscription, 510000, 10000);

        $token = $invoice->ensurePublicToken();

        foreach ([
            SendTenantNotificationJob::EVENT_RENEWAL_INVOICE,
            SendTenantNotificationJob::EVENT_PAYMENT_REMINDER,
            SendTenantNotificationJob::EVENT_SUSPENDED,
        ] as $event) {
            [$email, $wa] = $this->renderNotification($invoice, $event);

            foreach (['email' => $email, 'wa' => $wa] as $kanal => $isi) {
                $this->assertStringContainsString(
                    '/tagihan/' . $token,
                    $isi,
                    "Tautan {$kanal} untuk event {$event} harus mengarah ke halaman tagihan kita."
                );
                $this->assertStringNotContainsString(
                    'duitku.com',
                    $isi,
                    "Tautan {$kanal} untuk event {$event} tidak boleh menunjuk langsung ke gateway."
                );
                $this->assertStringNotContainsString(
                    'duitku.test',
                    $isi,
                    "Tautan {$kanal} untuk event {$event} tidak boleh menunjuk ke gateway sandbox."
                );
            }
        }
    }

    public function test_tautan_notifikasi_memakai_host_platform_walau_tenant_ditangguhkan(): void
    {
        // Saat tenant ditangguhkan, `{subdomain}.{host}` menampilkan halaman
        // status — tautan bayar harus tetap bekerja, jadi hostnya host platform.
        [, $subscription] = $this->activeSubscription(now()->subDays(10));
        $invoice = $this->makeRenewalInvoice($subscription, 510000, 10000);

        [$email, $wa] = $this->renderNotification($invoice, SendTenantNotificationJob::EVENT_SUSPENDED);

        foreach ([$email, $wa] as $isi) {
            $this->assertStringContainsString(config('app.url'), $isi);
            $this->assertStringNotContainsString('toko-notif-', $isi, 'Tautan tidak boleh memakai subdomain tenant.');
        }
    }

    public function test_tautan_notifikasi_tetap_sama_setelah_pembayaran_diregenerasi(): void
    {
        // D13.1: token tetap. Tautan yang sudah dikirim tidak boleh berubah arti
        // hanya karena tautan bayarnya dibuat ulang.
        [, $subscription] = $this->activeSubscription(now()->subDay());
        $invoice = $this->makeRenewalInvoice($subscription, 510000, 10000);

        [$emailSebelum] = $this->renderNotification($invoice, SendTenantNotificationJob::EVENT_PAYMENT_REMINDER);

        $invoice->forceFill([
            'metadata' => array_replace_recursive($invoice->metadata ?: [], [
                'duitku' => ['payment_url' => 'https://contoh-lain.test/pay/baru'],
            ]),
        ])->save();

        [$emailSesudah] = $this->renderNotification($invoice->fresh(), SendTenantNotificationJob::EVENT_PAYMENT_REMINDER);

        $this->assertSame(
            $this->tautanBayar($emailSebelum),
            $this->tautanBayar($emailSesudah),
            'Tautan yang sudah terkirim tidak boleh berubah saat tautan bayar diregenerasi.'
        );
    }

    private function tautanBayar(string $html): ?string
    {
        return preg_match('#https?://[^\s"<]*/tagihan/[a-f0-9]+#', $html, $m) === 1 ? $m[0] : null;
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

    /**
     * Task 6: penutup siklus. Saat invoice benar-benar mati, owner HARUS dapat
     * kabar — kalau tidak, tautan yang dia pegang berhenti bekerja tanpa
     * penjelasan, dan satu-satunya jalur pulang (halaman tagihan) jadi tidak
     * pernah ditawarkan.
     */
    public function test_invoice_kedaluwarsa_mengirim_notifikasi_sekali_saja(): void
    {
        Bus::fake([SendTenantNotificationJob::class]);

        [$tenant, $subscription] = $this->activeSubscription(now()->addDays(5), Tenant::STATUS_PENDING_PAYMENT);
        $invoice = $this->makeRenewalInvoice($subscription, 500000, 0);

        $service = app(TenantProvisioningService::class);

        $service->markInvoiceExpired($invoice);
        $service->markInvoiceExpired($invoice->fresh());

        $invoice->refresh();

        $this->assertSame(SubscriptionInvoice::STATUS_EXPIRED, $invoice->status);
        $this->assertNotNull(
            data_get($invoice->metadata, 'notifications.expired_sent_at'),
            'Jejak waktu pemberitahuan harus tercatat supaya tidak terkirim dua kali.'
        );

        Bus::assertDispatchedTimes(SendTenantNotificationJob::class, 1);
        Bus::assertDispatched(
            SendTenantNotificationJob::class,
            fn (SendTenantNotificationJob $job) => $job->event === SendTenantNotificationJob::EVENT_INVOICE_EXPIRED
                && $job->invoiceId === $invoice->id
        );
    }

    public function test_pesan_invoice_kedaluwarsa_menawarkan_jalan_memperbaiki(): void
    {
        // Invoice yang mati berarti tautan lama sudah tidak berlaku. Pesannya
        // harus memberi langkah berikutnya, bukan hanya memberi tahu.
        [, $subscription] = $this->activeSubscription(now()->addDays(5), Tenant::STATUS_PENDING_PAYMENT);
        $invoice = $this->makeRenewalInvoice($subscription, 500000, 0);

        [$email, $wa] = $this->renderNotification($invoice, SendTenantNotificationJob::EVENT_INVOICE_EXPIRED);

        $this->assertStringContainsString('expired', $email);
        $this->assertStringContainsString('support', $email);
        $this->assertStringContainsString('expired', $wa);
        $this->assertStringContainsString('support', $wa);
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /** @return array{0: Tenant, 1: Subscription} */
    private function activeSubscription(Carbon $periodEnd, string $tenantStatus = Tenant::STATUS_ACTIVE): array
    {
        $owner = User::factory()->create(['role' => 'Gold', 'email' => 'owner-' . uniqid() . '@example.test']);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Uji Notif',
            'subdomain' => 'toko-notif-' . uniqid(),
            'tier' => 'starter',
            'status' => $tenantStatus,
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
