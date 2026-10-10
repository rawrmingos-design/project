<?php

namespace Tests\Feature\Tenancy;

use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\DuitkuPopClient;
use App\Tenancy\TenantProvisioningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Billing berulang: perpanjangan (H-3), denda flat saat masa tenggang,
 * suspend setelah tenggang habis, dan pemulihan setelah dibayar.
 *
 * Keputusan: B1 H-3 · B2 tenggang 3 hari · B3 tenant disuspend, data utuh ·
 * B4 subdomain TIDAK dilepas · B5 harga tier sekarang · B7 denda Rp 10.000 flat.
 */
class BillingRenewalTest extends TestCase
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

    // ---------------------------------------------------------------------
    // Fase pra-jatuh-tempo (H-3): tanpa denda
    // ---------------------------------------------------------------------

    public function test_invoice_perpanjangan_terbit_saat_masuk_jendela_h3(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->addDays(2));

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);

        $invoice = $this->renewalInvoices($subscription);
        $this->assertCount(1, $invoice);
        $this->assertSame(500000, (int) $invoice->first()->amount, 'Di jendela H-3 belum ada denda.');
        $this->assertSame(SubscriptionInvoice::STATUS_PENDING, $invoice->first()->status);
        $this->assertNotNull($invoice->first()->gateway_ref);
        $this->assertNotSame($subscription->gateway_ref, $invoice->first()->gateway_ref);
    }

    public function test_invoice_perpanjangan_terbit_tepat_sekali(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->addDay());

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);
        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);
        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);

        $this->assertCount(1, $this->renewalInvoices($subscription), 'Tiga kali dijalankan tetap satu invoice.');
    }

    public function test_langganan_yang_masih_lama_tidak_ditagih(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->addDays(20));

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);

        $this->assertCount(0, $this->renewalInvoices($subscription), 'Belum waktunya ditagih.');
    }

    public function test_tenant_belum_pernah_aktif_tidak_ditagih(): void
    {
        // pending_payment: belum pernah bayar sama sekali → jangan tagih.
        $owner = User::factory()->create(['role' => 'Member']);
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Belum Bayar',
            'subdomain' => 'belum-bayar-' . uniqid(),
            'tier' => 'starter',
            'status' => Tenant::STATUS_PENDING_PAYMENT,
        ]);
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_PENDING,
            'gateway_ref' => 'SUB-PENDING-1',
            'current_period_end' => now()->addDay(),
        ]);

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);

        $this->assertCount(0, $this->renewalInvoices($subscription));
    }

    // ---------------------------------------------------------------------
    // Fase masa tenggang: denda flat Rp 10.000 (B7)
    // ---------------------------------------------------------------------

    public function test_masa_tenggang_terbit_invoice_berdenda_dan_invoice_lama_di_expire(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDay());

        // Invoice tanpa denda sudah terbit lebih dulu (dari fase H-3).
        $lama = $this->makePendingRenewal($subscription, 500000, $subscription->current_period_end);

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);

        $this->assertSame(
            SubscriptionInvoice::STATUS_EXPIRED,
            $lama->fresh()->status,
            'Invoice tanpa denda harus di-expire supaya tidak ada dua tagihan hidup.'
        );

        $pending = $this->renewalInvoices($subscription)->where('status', SubscriptionInvoice::STATUS_PENDING);
        $this->assertCount(1, $pending);
        $this->assertSame(510000, (int) $pending->first()->amount, 'Harga dasar 500.000 + denda flat 10.000.');
        $this->assertSame(10000, (int) data_get($pending->first()->metadata, 'late_fee'));
        $this->assertNotSame($lama->gateway_ref, $pending->first()->gateway_ref, 'Wajib ref baru (kolom unik).');
    }

    public function test_denda_hanya_dikenakan_sekali_walau_command_diulang(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDay());
        $this->makePendingRenewal($subscription, 500000, $subscription->current_period_end);

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);
        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);
        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);

        $berdenda = $this->renewalInvoices($subscription)->filter(
            fn (SubscriptionInvoice $i) => (int) data_get($i->metadata, 'late_fee', 0) > 0
        );

        $this->assertCount(1, $berdenda, 'Denda tidak boleh berlipat.');
        $this->assertSame(
            510000,
            (int) $berdenda->first()->amount,
            'Denda flat — tidak bertambah per hari.'
        );
    }

    public function test_denda_tidak_mengubah_amount_invoice_yang_link_nya_masih_hidup(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDay());

        // Invoice berdenda sudah terbit dan link-nya masih hidup (due_date belum lewat).
        $berdenda = $this->makePendingRenewal($subscription, 510000, $subscription->current_period_end, 10000);

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);

        $this->assertSame(510000, (int) $berdenda->fresh()->amount, 'Nominal invoice berlink hidup tidak boleh diubah.');
        $this->assertSame(SubscriptionInvoice::STATUS_PENDING, $berdenda->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // Suspend setelah tenggang habis (B2, B3, B4)
    // ---------------------------------------------------------------------

    public function test_tenant_disuspend_setelah_tenggang_habis(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDays(4));

        $this->artisan('billing:suspend-overdue')->assertExitCode(0);

        $this->assertSame(Tenant::STATUS_SUSPENDED, $tenant->fresh()->status);
    }

    public function test_belum_suspend_kalau_masih_di_dalam_tenggang(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDay());

        $this->artisan('billing:suspend-overdue')->assertExitCode(0);

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status, 'Masih tenggang — jangan disuspend.');
    }

    public function test_suspend_tepat_sekali(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDays(10));

        // Realistis: tenant sudah ditagih perpanjangan tapi tidak membayar.
        $this->makePendingRenewal($subscription, 510000, $subscription->current_period_end, 10000);

        $this->artisan('billing:suspend-overdue')->assertExitCode(0);
        $this->artisan('billing:suspend-overdue')->assertExitCode(0);
        $this->artisan('billing:suspend-overdue')->assertExitCode(0);

        $this->assertSame(Tenant::STATUS_SUSPENDED, $tenant->fresh()->status);
        $this->assertCount(1, $this->suspensionEvents($tenant), 'Suspend tidak boleh dicatat berkali-kali.');
    }

    public function test_suspend_tidak_menghapus_data_dan_tidak_melepas_subdomain(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDays(4));

        Pembelian::factory()->create([
            'tenant_id' => $tenant->id,
            'harga' => 12000,
            'profit' => 2000,
            'status' => 'Success',
        ]);

        $subdomainAwal = $tenant->subdomain;
        $pembelianAwal = Pembelian::query()->where('tenant_id', $tenant->id)->count();

        $this->artisan('billing:suspend-overdue')->assertExitCode(0);

        $tenant->refresh();
        $this->assertSame(Tenant::STATUS_SUSPENDED, $tenant->status);
        $this->assertSame($subdomainAwal, $tenant->subdomain, 'B4: subdomain tidak boleh dilepas.');
        $this->assertSame(
            $pembelianAwal,
            Pembelian::query()->where('tenant_id', $tenant->id)->count(),
            'Data tenant tidak boleh dihapus.'
        );
    }

    public function test_tenant_yang_sudah_bayar_tidak_disuspend(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->subDays(4));

        $this->makePaidRenewal($subscription);

        $this->artisan('billing:suspend-overdue')->assertExitCode(0);

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
    }

    // ---------------------------------------------------------------------
    // Perpanjangan saat dibayar (B5) — anti potong masa aktif
    // ---------------------------------------------------------------------

    public function test_bayar_saat_periode_masih_aktif_menambah_dari_periode_lama(): void
    {
        Carbon::setTestNow('2026-10-10 10:00:00');

        [$tenant, $subscription] = $this->activeSubscription(now()->addDays(2));

        // Periode berjalan berakhir 12 Okt. Bayar sekarang harus menghasilkan
        // 12 Nov (tambah penuh dari akhir periode) — BUKAN 10 Nov (now()+1 bulan),
        // yang berarti memotong 2 hari masa aktif yang sudah dibayar.
        $invoice = $this->makePendingRenewal($subscription, 500000, $subscription->current_period_end);

        app(TenantProvisioningService::class)->markInvoicePaid($invoice, $invoice->gateway_ref);

        $subscription->refresh();
        $this->assertSame(
            '2026-11-12 10:00:00',
            $subscription->current_period_end->format('Y-m-d H:i:s'),
            'Bayar lebih awal TIDAK boleh memotong masa aktif: akhir periode 12 Okt + 1 bulan = 12 Nov.'
        );
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_bayar_di_masa_tenggang_memulihkan_tenant_dan_memperpanjang_periode(): void
    {
        Carbon::setTestNow('2026-10-12 10:00:00');

        [$tenant, $subscription] = $this->activeSubscription(Carbon::parse('2026-10-10 10:00:00'));

        // Invoice berdenda (B7).
        $invoice = $this->makePendingRenewal($subscription, 510000, $subscription->current_period_end, 10000);

        $this->artisan('billing:suspend-overdue')->assertExitCode(0); // belum, masih tenggang
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);

        app(TenantProvisioningService::class)->markInvoicePaid($invoice, $invoice->gateway_ref);

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
        $this->assertSame(
            '2026-11-10 10:00:00',
            $subscription->fresh()->current_period_end->format('Y-m-d H:i:s'),
            'Periode habis 10 Okt + 1 bulan.'
        );
        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $invoice->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_bayar_setelah_suspend_mengaktifkan_kembali_tenant(): void
    {
        Carbon::setTestNow('2026-10-14 10:00:00');

        [$tenant, $subscription] = $this->activeSubscription(Carbon::parse('2026-10-10 10:00:00'));

        $this->artisan('billing:suspend-overdue')->assertExitCode(0);
        $this->assertSame(Tenant::STATUS_SUSPENDED, $tenant->fresh()->status);

        $invoice = $this->makePendingRenewal($subscription, 510000, $subscription->current_period_end, 10000);
        app(TenantProvisioningService::class)->markInvoicePaid($invoice, $invoice->gateway_ref);

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status, 'Bayar harus mengaktifkan kembali.');
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_membayar_satu_invoice_melunasi_tagihan_lain_untuk_periode_sama(): void
    {
        [$tenant, $subscription] = $this->activeSubscription(now()->addDay());

        $dibayar = $this->makePendingRenewal($subscription, 500000, $subscription->current_period_end);
        $kembar = $this->makePendingRenewal($subscription, 510000, $subscription->current_period_end, 10000);

        app(TenantProvisioningService::class)->markInvoicePaid($dibayar, $dibayar->gateway_ref);

        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $dibayar->fresh()->status);
        $this->assertSame(
            SubscriptionInvoice::STATUS_CANCELLED,
            $kembar->fresh()->status,
            'Tagihan kembar untuk periode yang sama harus dibatalkan supaya tidak dobel tagih.'
        );
    }

    public function test_saklar_mati_berarti_tidak_ada_penagihan(): void
    {
        config(['billing.renewal_enabled' => false]);

        [$tenant, $subscription] = $this->activeSubscription(now()->subDays(10));

        $this->artisan('billing:renew-subscriptions')->assertExitCode(0);
        $this->artisan('billing:suspend-overdue')->assertExitCode(0);

        $this->assertCount(0, $this->renewalInvoices($subscription));
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status, 'Saklar mati = tidak ada suspend.');
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /** @return array{0: Tenant, 1: Subscription} */
    private function activeSubscription(Carbon $periodEnd): array
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Uji Billing',
            'subdomain' => 'toko-uji-' . uniqid(),
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-AWAL-' . strtoupper(uniqid()),
            'current_period_start' => $periodEnd->copy()->subMonth(),
            'current_period_end' => $periodEnd,
        ]);

        return [$tenant, $subscription];
    }

    private function makePendingRenewal(
        Subscription $subscription,
        int $amount,
        Carbon $periodEnd,
        int $lateFee = 0,
    ): SubscriptionInvoice {
        return SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDay(),
            'metadata' => [
                'source' => 'renewal',
                'kind' => 'renewal',
                'period_end' => $periodEnd->toIso8601String(),
                'late_fee' => $lateFee,
                'duitku' => [
                    'reference' => 'DUITKU-RENEW-' . strtoupper(uniqid()),
                    'payment_url' => 'https://sandbox.duitku.test/pay/renew',
                ],
            ],
        ]);
    }

    private function makePaidRenewal(Subscription $subscription): SubscriptionInvoice
    {
        return SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 510000,
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->subDay(),
            'metadata' => [
                'kind' => 'renewal',
                'period_end' => $subscription->current_period_end?->toIso8601String(),
                'late_fee' => 10000,
            ],
        ]);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SubscriptionInvoice> */
    private function renewalInvoices(Subscription $subscription)
    {
        return SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('metadata->kind', 'renewal')
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, SubscriptionInvoiceEvent> */
    private function suspensionEvents(Tenant $tenant)
    {
        return \App\Models\SubscriptionInvoiceEvent::query()
            ->whereIn('subscription_invoice_id', SubscriptionInvoice::query()
                ->where('subscription_id', $tenant->subscriptions()->pluck('id'))
                ->pluck('id'))
            ->whereIn('meta->action', ['suspend'])
            ->get();
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
