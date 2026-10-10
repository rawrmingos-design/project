<?php

namespace Tests\Feature\Tenancy;

use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\DuitkuPopClient;
use App\Tenancy\TenantProvisioningService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Billing berulang butuh LEBIH DARI SATU invoice per langganan.
 * Kolom `subscription_invoices.gateway_ref` UNIK, jadi setiap invoice
 * WAJIB punya ref sendiri.
 *
 * Bug yang dikunci di sini: jalur pendaftaran menurunkan gateway_ref invoice
 * DARI gateway_ref langganan. Akibatnya ref itu terpakai oleh invoice pertama,
 * dan invoice periode berikutnya tidak akan pernah bisa dibuat.
 */
class SubscriptionInvoiceRefTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);
    }

    /**
     * Bukti bahwa batasannya NYATA: dua invoice dengan ref yang sama ditolak DB.
     * Ini alasan kenapa invoice tidak boleh mewarisi ref dari langganan.
     */
    public function test_gateway_ref_invoice_unik_sehingga_dua_invoice_tidak_boleh_se_ref(): void
    {
        [, , $subscription] = $this->makeActiveSubscription();

        SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PAID,
            'gateway' => 'duitku',
            'gateway_ref' => 'SUB-REF-DIPAKAI',
            'due_date' => now()->addDay(),
        ]);

        $this->expectException(QueryException::class);

        // Ref yang sama → ditolak. Inilah yang bikin billing berulang mustahil
        // kalau invoice mewarisi ref langganan.
        SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 510000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => 'SUB-REF-DIPAKAI',
            'due_date' => now()->addDay(),
        ]);
    }

    /**
     * Kalau invoice mewarisi ref langganan, periode kedua mustahil ditagih.
     * Test ini mengunci perbaikan: invoice WAJIB punya ref sendiri.
     */
    public function test_invoice_pendaftaran_punya_gateway_ref_sendiri_bukan_ref_langganan(): void
    {
        $this->fakeDuitku();

        $this->postJson('/api/tenant/register', [
            'name' => 'Raka Billing',
            'email' => 'raka-billing@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Raka Billing Store',
            'subdomain' => 'raka-billing',
            'tier' => 'starter',
            'terms_accepted' => true,
        ])->assertCreated();

        $subscription = Subscription::query()->firstOrFail();
        $invoice = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->firstOrFail();

        $this->assertNotNull($invoice->gateway_ref, 'Invoice harus punya gateway_ref (merchantOrderId Duitku).');

        $this->assertNotSame(
            $subscription->gateway_ref,
            $invoice->gateway_ref,
            'Invoice tidak boleh memakai gateway_ref langganan: kolomnya unik, jadi invoice periode berikutnya tidak akan bisa dibuat.'
        );
    }

    /**
     * Inti kebutuhan billing berulang: setelah invoice periode pertama ada,
     * invoice periode kedua HARUS bisa dibuat untuk langganan yang sama.
     */
    public function test_invoice_periode_berikutnya_bisa_dibuat_untuk_langganan_yang_sama(): void
    {
        $this->fakeDuitku();

        $this->postJson('/api/tenant/register', [
            'name' => 'Dua Periode',
            'email' => 'dua-periode@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Dua Periode Store',
            'subdomain' => 'dua-periode',
            'tier' => 'starter',
            'terms_accepted' => true,
        ])->assertCreated();

        $subscription = Subscription::query()->firstOrFail();
        $periode1 = SubscriptionInvoice::query()->where('subscription_id', $subscription->id)->firstOrFail();

        // Tandai periode pertama lunas, lalu tagih periode berikutnya —
        // persis yang akan dilakukan penagihan berulang.
        $periode1->forceFill([
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
        ])->save();

        $periode2 = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => 'SUB-PERIODE-2-UNIK',
            'due_date' => now()->addDay(),
            'metadata' => ['source' => 'renewal'],
        ]);

        $this->assertNotSame($periode1->gateway_ref, $periode2->gateway_ref);
        $this->assertSame(
            2,
            SubscriptionInvoice::query()->where('subscription_id', $subscription->id)->count(),
            'Satu langganan harus bisa punya invoice periode pertama dan kedua.'
        );
    }

    /**
     * Setelah invoice ke-2 dibayar, langganan menunjuk ref invoice terbaru
     * supaya webhook tetap menemukan invoice yang benar (invarian lama
     * dipertahankan sebagai jaring pengaman, bukan sebagai sumber ref).
     */
    public function test_pembayaran_invoice_kedua_tetap_ditemukan_webhook(): void
    {
        $this->fakeDuitku();
        [, $tenant, $subscription] = $this->makeActiveSubscription(['gateway_ref' => 'SUB-LAMA-001']);

        $periode2 = SubscriptionInvoice::query()->create([
            'subscription_id' => $subscription->id,
            'amount' => 510000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => 'SUB-PERIODE-2-UNIK',
            'due_date' => now()->addDay(),
            'metadata' => ['source' => 'renewal', 'duitku' => ['reference' => 'DUITKU-REF-2']],
        ]);

        app(TenantProvisioningService::class)->markInvoicePaid($periode2, 'SUB-PERIODE-2-UNIK');

        $this->assertSame(
            'SUB-PERIODE-2-UNIK',
            $subscription->fresh()->gateway_ref,
            'Langganan harus menunjuk ref invoice terakhir yang dibayar.'
        );

        $ditemukan = SubscriptionInvoice::query()
            ->where('gateway', 'duitku')
            ->where('gateway_ref', 'SUB-PERIODE-2-UNIK')
            ->first();

        $this->assertNotNull($ditemukan, 'Webhook harus tetap bisa menemukan invoice lewat gateway_ref.');
        $this->assertSame($periode2->id, $ditemukan->id);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->fresh()->status);
    }

    /** @return array{0: User, 1: Tenant, 2: Subscription} */
    private function makeActiveSubscription(array $subscriptionAttrs = []): array
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Langganan Uji',
            'subdomain' => 'langganan-uji-' . uniqid(),
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::query()->create(array_merge([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-LAMA-001',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ], $subscriptionAttrs));

        return [$owner, $tenant, $subscription];
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
