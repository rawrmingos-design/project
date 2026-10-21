<?php

namespace Tests\Feature\Tenancy;

use App\Models\Layanan;
use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\TierPricing;
use App\Models\User;
use App\Services\Payments\DuitkuPopClient;
use App\Tenancy\BillingRenewalService;
use App\Tenancy\TenantPricingService;
use App\Tenancy\TierEntitlements;
use App\Tenancy\TierPricingResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SATU ALUR PEMBELI, UJUNG KE UJUNG.
 *
 * Tes-tes lain menguji potongan: registrasi, webhook, penagihan, suspend. Yang
 * tidak pernah dibuktikan adalah bahwa potongan-potongan itu menyambung jadi
 * satu perjalanan yang bisa dilalui pembeli platform tanpa developer.
 *
 * Alur yang dijalankan di sini, berurutan, dengan state yang benar-benar
 * berpindah antar langkah:
 *
 *   1. calon pembeli membuka halaman harga dan melihat harga dari DB
 *   2. admin mengubah harga di panel → kartu harga ikut berubah
 *   3. calon pembeli mendaftar → tenant pending + invoice diterbitkan
 *   4. callback Duitku salah tanda tangan / nominal → ditolak, tidak aktif
 *   5. callback Duitku benar → tenant aktif, owner jadi tenant_owner,
 *      periode maju sesuai durasi yang dibeli
 *   6. callback yang sama datang dua kali → tidak menambah periode
 *   7. tenant berjualan: harga jual & profit dihitung dari modal tier-nya
 *   8. order sukses mengkredit komisi owner tepat sekali
 *   9. H-3 sebelum periode habis → tagihan perpanjangan terbit sendiri
 *  10. lewat tenggang → tenant disuspend, data & subdomain tidak dihapus
 *  11. tenant membayar tagihan berdenda → aktif kembali, periode maju
 */
class TenantLifecycleEndToEndTest extends TestCase
{
    use RefreshDatabase;

    private SettingWeb $settings;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenancy.disabled' => false,
            'app.url' => 'https://topupengine.test',
            'billing.renewal_enabled' => true,
            'billing.reminder_days_before' => 3,
            'billing.grace_days' => 3,
            'billing.late_fee' => 10000,
        ]);
    }

    protected function tearDown(): void
    {
        app(\App\Tenancy\TenantContext::class)->clear();
        app()->forgetInstance('tenant');
        parent::tearDown();
    }

    public function test_perjalanan_lengkap_pembeli_platform_dari_daftar_sampai_perpanjangan(): void
    {
        $this->siapkanPlatform();

        // ── 1. halaman harga menampilkan angka dari DB ────────────────────
        $this->get('https://topupengine.test/id/reseller-topup')
            ->assertOk()
            ->assertSee('330.000')
            ->assertSee('800.000');

        // ── 2. admin mengubah harga di panel → halaman ikut ───────────────
        TierPricing::query()->where('tier', 'starter')->first()
            ->update(['price_3_month' => 300000, 'price_12_month' => 750000]);
        app(TierPricingResolver::class)->flush();

        $this->get('https://topupengine.test/id/reseller-topup')
            ->assertOk()
            ->assertSee('300.000')
            ->assertSee('750.000')
            ->assertDontSee('330.000');

        // ── 3. pendaftaran: tenant pending + invoice ──────────────────────
        $daftar = $this->postJson('/api/tenant/register', [
            'name' => 'Bagas Pemilik',
            'email' => 'bagas@example.test',
            'password' => 'password123',
            'no_wa' => '081298765432',
            'store_name' => 'Bagas Topup',
            'subdomain' => 'bagas-topup',
            'tier' => 'business',
            'duration_months' => 3,
            'terms_accepted' => true,
        ]);

        $daftar->assertCreated()
            // business 3 bulan = 435.000 (harga panel, bukan konstanta kode)
            ->assertJsonPath('invoice.amount', 435000)
            ->assertJsonPath('tenant.status', Tenant::STATUS_PENDING_PAYMENT);

        $tenant = Tenant::query()->where('subdomain', 'bagas-topup')->firstOrFail();
        $subscription = $tenant->subscriptions()->firstOrFail();
        $invoice = SubscriptionInvoice::query()->where('subscription_id', $subscription->id)->firstOrFail();
        $owner = User::query()->where('email', 'bagas@example.test')->firstOrFail();

        $this->assertSame(3, (int) $subscription->duration_months);
        $this->assertSame(Tenant::STATUS_PENDING_PAYMENT, $tenant->status);

        // Toko belum aktif → pembeli TIDAK melihat katalog, tapi halaman status.
        // 200 memang disengaja (lihat InactiveTenantStatusPageTest): 404 akan
        // membuat calon pembeli mengira tokonya tidak ada.
        $this->get('https://bagas-topup.topupengine.test/')
            ->assertOk()
            ->assertDontSee('Bagas Topup');

        // ── 4. callback palsu ditolak, tenant TIDAK aktif ─────────────────
        $this->post('https://topupengine.test/wejizy/duitku/subscription/callback', [
            'merchantCode' => $this->settings->duitku_merchant_code,
            'amount' => (string) $invoice->amount,
            'merchantOrderId' => $invoice->gateway_ref,
            'resultCode' => '00',
            'reference' => data_get($invoice->metadata, 'duitku.reference'),
            'signature' => 'tanda-tangan-palsu',
        ])->assertStatus(400);

        $this->assertSame(Tenant::STATUS_PENDING_PAYMENT, $tenant->fresh()->status);
        $this->assertSame(SubscriptionInvoice::STATUS_PENDING, $invoice->fresh()->status);

        // Nominal diubah juga harus ditolak walau tanda tangan benar.
        $this->post('https://topupengine.test/wejizy/duitku/subscription/callback',
            $this->duitkuPayload($invoice, ['amount' => '1000'])
        )->assertStatus(400);

        $this->assertSame(Tenant::STATUS_PENDING_PAYMENT, $tenant->fresh()->status);

        // ── 5. callback benar → aktif ─────────────────────────────────────
        $this->post('https://topupengine.test/wejizy/duitku/subscription/callback',
            $this->duitkuPayload($invoice)
        )->assertOk()->assertSee('SUCCESS');

        $tenant->refresh();
        $subscription->refresh();
        $owner->refresh();

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->status);
        $this->assertSame(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame(SubscriptionInvoice::STATUS_PAID, $invoice->fresh()->status);
        // Peran pemilik toko, bukan Gold: Gold akan mengunci dia di halaman
        // deposit tokonya sendiri dan memberinya harga retail platform.
        $this->assertSame('tenant_owner', $owner->role);
        // 3 bulan, bukan 1 — durasi yang dia bayar.
        $this->assertSame(
            3,
            (int) $subscription->current_period_start->diffInMonths($subscription->current_period_end)
        );

        // Toko sekarang bisa dibuka.
        $this->get('https://bagas-topup.topupengine.test/')->assertOk()->assertSee('Bagas Topup');

        // ── 6. callback duplikat tidak menambah periode ───────────────────
        $periodeSetelahBayar = $subscription->current_period_end->toDateTimeString();

        $this->post('https://topupengine.test/wejizy/duitku/subscription/callback',
            $this->duitkuPayload($invoice)
        )->assertOk();

        $this->assertSame(
            $periodeSetelahBayar,
            $subscription->fresh()->current_period_end->toDateTimeString(),
            'callback ganda tidak boleh menumpuk bulan'
        );

        // ── 7. tenant berjualan: harga jual dari modal tier-nya ───────────
        $layanan = Layanan::factory()->create(['harga' => 100000]);

        $hargaTenant = app(TenantPricingService::class)->forLayanan($layanan, $tenant);

        // business = markup platform 3% ; markup tenant default 8%
        $this->assertSame(100000, $hargaTenant['modal']);
        $this->assertSame(103000, $hargaTenant['modal_tenant']);
        $this->assertSame(108000, $hargaTenant['sell_price']);
        $this->assertSame(5000, $hargaTenant['profit']);

        // ── 8. order sukses mengkredit komisi owner sekali ────────────────
        $owner->forceFill(['balance' => 0])->save();
        $order = \App\Models\Pembelian::factory()->create([
            'tenant_id' => $tenant->id,
            'status' => 'Proses',
            'profit' => 5000,
            'tenant_commission_credited_at' => null,
        ]);

        $order->update(['status' => 'Success']);
        $order->refresh()->update(['status' => 'Sukses']);

        $this->assertSame(5000, (int) $owner->fresh()->balance, 'komisi dikredit tepat sekali');

        // ── 9. H-3 sebelum habis → tagihan perpanjangan terbit sendiri ────
        $this->travelTo($subscription->fresh()->current_period_end->copy()->subDays(2));

        $statistik = app(BillingRenewalService::class)->renewDueSubscriptions(true);

        $this->assertSame(1, $statistik['created'], 'satu tagihan perpanjangan terbit');

        $tagihanBaru = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', SubscriptionInvoice::STATUS_PENDING)
            ->firstOrFail();

        // Perpanjangan memakai durasi yang sama (3 bulan), tanpa denda karena
        // masih di dalam periode berjalan.
        $this->assertSame(435000, (int) $tagihanBaru->amount);
        // Tagihan hidup dengan nominal benar → dijalankan lagi tidak dobel.
        $this->assertSame(1, app(BillingRenewalService::class)->renewDueSubscriptions(true)['skipped']);

        // ── 10. masuk masa tenggang → tagihan LAMA di-expire, terbit tagihan
        //         berdenda. Urutannya penting: denda hanya berlaku di dalam
        //         tenggang, bukan setelahnya.
        $this->travelTo($subscription->fresh()->current_period_end->copy()->addDay());

        $statistikTenggang = app(BillingRenewalService::class)->renewDueSubscriptions(true);

        $this->assertSame(1, $statistikTenggang['expired'], 'tagihan tanpa denda dimatikan');

        $tagihanDenda = SubscriptionInvoice::query()
            ->where('subscription_id', $subscription->id)
            ->where('status', SubscriptionInvoice::STATUS_PENDING)
            ->orderByDesc('id')
            ->firstOrFail();

        $this->assertSame(445000, (int) $tagihanDenda->amount, '435.000 + denda 10.000');
        $this->assertSame(10000, (int) data_get($tagihanDenda->metadata, 'late_fee'));

        // Dijalankan berulang tidak menumpuk denda.
        app(BillingRenewalService::class)->renewDueSubscriptions(true);
        $this->assertCount(
            1,
            SubscriptionInvoice::query()
                ->where('subscription_id', $subscription->id)
                ->where('status', SubscriptionInvoice::STATUS_PENDING)
                ->get(),
            'hanya satu tagihan hidup'
        );

        // ── 11. lewat tenggang → suspend, data utuh ───────────────────────
        $this->travelTo($subscription->fresh()->current_period_end->copy()->addDays(5));

        $suspend = app(BillingRenewalService::class)->suspendOverdue(true);

        $this->assertSame(1, $suspend['suspended']);
        $this->assertSame(Tenant::STATUS_SUSPENDED, $tenant->fresh()->status);
        // Yang berubah hanya status: nama, subdomain, dan saldo tetap ada.
        $this->assertSame('Bagas Topup', $tenant->fresh()->name);
        $this->assertSame('bagas-topup', $tenant->fresh()->subdomain);
        $this->assertSame(5000, (int) $owner->fresh()->balance);
        // Data toko tidak dihapus; katalog tetap ada di DB.
        $this->assertDatabaseHas('tenants', ['subdomain' => 'bagas-topup']);

        // Dijalankan lagi tidak menyuspend dua kali.
        $this->assertSame(0, app(BillingRenewalService::class)->suspendOverdue(true)['suspended']);

        // ── 12. bayar tagihan berdenda → aktif kembali ────────────────────
        $periodeSebelum = $subscription->fresh()->current_period_end->toDateTimeString();

        $this->post('https://topupengine.test/wejizy/duitku/subscription/callback',
            $this->duitkuPayload($tagihanDenda)
        )->assertOk()->assertSee('SUCCESS');

        $tenant->refresh();
        $subscription->refresh();

        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->status, 'bayar → aktif kembali');
        $this->assertTrue(
            $subscription->current_period_end->toDateTimeString() !== $periodeSebelum,
            'periode maju setelah pembayaran'
        );
        // Aktif kembali → toko bisa dibuka lagi.
        $this->get('https://bagas-topup.topupengine.test/')->assertOk();
    }

    /**
     * Fitur yang dibatasi paket harus tetap terkunci di tengah alur ini:
     * business punya custom domain, dan gate-nya membaca paket yang dibayar.
     */
    public function test_hak_fitur_mengikuti_paket_yang_dibayar(): void
    {
        $this->siapkanPlatform();

        $owner = User::factory()->create(['role' => 'tenant_owner']);
        $starter = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Starter Store',
            'subdomain' => 'starter-store',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 8],
        ]);
        $business = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Business Store',
            'subdomain' => 'business-store',
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 8],
        ]);

        $entitlements = app(TierEntitlements::class);

        $this->assertFalse($entitlements->has($starter, 'custom_domain'));
        $this->assertTrue($entitlements->has($business, 'custom_domain'));

        // Admin menaikkan paket starter → hak ikut naik tanpa deploy.
        $starter->forceFill(['tier' => 'business'])->save();
        app(TierEntitlements::class)->flush();

        $this->assertTrue(app(TierEntitlements::class)->has($starter->fresh(), 'custom_domain'));
    }

    // ───────────────────────── helper ─────────────────────────

    private function siapkanPlatform(): void
    {
        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(TierPricingResolver::class)->flush();
        app(TierEntitlements::class)->flush();

        $this->settings = $this->duitkuSettings();

        // Gateway tidak benar-benar dipanggil: yang diuji adalah alur kita,
        // bukan ketersediaan Duitku.
        $this->app->instance(DuitkuPopClient::class, new class extends DuitkuPopClient {
            public function createInvoice(array $params, \Duitku\Config $config): array
            {
                static $n = 0;
                $n++;

                return [
                    'statusCode' => '00',
                    'statusMessage' => 'SUCCESS',
                    'reference' => 'DUITKU-REF-' . $n,
                    'paymentUrl' => 'https://sandbox.duitku.test/pay/' . $n,
                    'amount' => $params['paymentAmount'],
                ];
            }
        });
    }

    private function duitkuSettings(): SettingWeb
    {
        return SettingWeb::query()->firstOrCreate(['id' => 1], [
            'judul_web' => 'TopupEngine Test',
            'deskripsi_web' => 'Deskripsi',
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

    private function duitkuPayload(SubscriptionInvoice $invoice, array $overrides = []): array
    {
        $payload = array_merge([
            'merchantCode' => $this->settings->duitku_merchant_code,
            'amount' => (string) $invoice->amount,
            'merchantOrderId' => $invoice->gateway_ref,
            'productDetail' => 'Langganan',
            'resultCode' => '00',
            'reference' => (string) data_get($invoice->metadata, 'duitku.reference'),
        ], $overrides);

        if (! array_key_exists('signature', $overrides)) {
            $payload['signature'] = md5(
                $payload['merchantCode'] .
                $payload['amount'] .
                $payload['merchantOrderId'] .
                $this->settings->duitku_merchant_key
            );
        }

        return $payload;
    }
}
