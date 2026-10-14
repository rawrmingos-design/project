<?php

namespace Tests\Feature\Billing;

use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\DuitkuPopClient;
use App\Tenancy\Contracts\CloudflareDnsClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * S4 — halaman sukses `/tagihan/{token}/sukses` + prasyarat backend B1–B4.
 *
 * Yang dijaga di sini:
 *  - B1  response pendaftaran memuat `public_token` + `billing_url`
 *  - B2  `returnUrl` Duitku menunjuk halaman sukses, dan tokennya SUDAH terbit
 *        sebelum tautan itu dibangun (kalau tidak, pembeli kembali ke 404)
 *  - B3  rute + controller + view halaman sukses
 *  - S4  "sukses" HANYA dari status invoice, tidak pernah dari kedatangan
 *        pengunjung; keadaan "belum terkonfirmasi" harus ada
 */
class PublicBillingSuccessPageTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://topupengine.test';

    /** Parameter yang benar-benar dikirim ke Duitku (untuk menguji B2). */
    private static array $duitkuParams = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$duitkuParams = [];

        config([
            'app.url' => self::BASE,
            'tenancy.disabled' => false,
        ]);

        // Limiter bisa menumpuk antar-test dalam satu proses.
        $this->withoutMiddleware(ThrottleRequests::class);

        $this->app->instance(CloudflareDnsClientInterface::class, new class implements CloudflareDnsClientInterface
        {
            public function hostnamesUnder(string $zoneId, string $suffix): array
            {
                return [];
            }
        });

        $this->fakeDuitku();
    }

    // ---------------------------------------------------------------------
    // B1: response pendaftaran membawa token + URL tagihan (keputusan #3)
    // ---------------------------------------------------------------------

    public function test_response_pendaftaran_membawa_public_token_dan_billing_url(): void
    {
        $response = $this->daftar('b1@example.test', 'tokob1')->assertCreated();

        $token = (string) $response->json('invoice.public_token');

        $this->assertNotSame('', $token, 'B1: token publik wajib ikut di response supaya form bisa mengarahkan.');
        $this->assertSame(64, strlen($token));

        $billingUrl = (string) $response->json('invoice.billing_url');

        $this->assertStringStartsWith(self::BASE . '/tagihan/', $billingUrl);
        $this->assertStringContainsString($token, $billingUrl);

        // Token di response harus SAMA dengan yang tersimpan — bukan token sekali pakai.
        $this->assertSame($token, (string) SubscriptionInvoice::query()->firstOrFail()->public_token);
    }

    public function test_billing_url_selalu_memakai_host_platform(): void
    {
        // Halaman tagihan & sukses hanya hidup di host platform
        // (`RequirePlatformHost` mem-404-kan host lain), jadi URL yang dikirim ke
        // pembeli tidak boleh menempelkan host lain.
        $response = $this->daftar('host@example.test', 'tokohost')->assertCreated();

        $this->assertSame(
            parse_url(self::BASE, PHP_URL_HOST),
            parse_url((string) $response->json('invoice.billing_url'), PHP_URL_HOST)
        );
    }

    public function test_register_ulang_tidak_mengubah_token(): void
    {
        // D13.1: token yang sudah terkirim lewat email/WA tidak boleh berputar.
        $pertama = $this->daftar('resume-b1@example.test', 'tokoresume')->assertCreated();
        $token = (string) $pertama->json('invoice.public_token');

        $kedua = $this->daftar('resume-b1@example.test', 'tokoresume')->assertOk();

        $this->assertSame($token, (string) $kedua->json('invoice.public_token'));
    }

    // ---------------------------------------------------------------------
    // B2: returnUrl Duitku → halaman sukses, token sudah terbit lebih dulu
    // ---------------------------------------------------------------------

    public function test_return_url_duitku_menunjuk_halaman_sukses(): void
    {
        $this->daftar('b2@example.test', 'tokob2')->assertCreated();

        $this->assertNotEmpty(self::$duitkuParams, 'Duitku harus dipanggil saat pendaftaran tier berbayar.');

        $returnUrl = (string) end(self::$duitkuParams)['returnUrl'];

        $this->assertStringEndsWith('/sukses', $returnUrl, 'B2: returnUrl harus halaman sukses, bukan form pendaftaran.');
        $this->assertStringStartsWith(self::BASE . '/tagihan/', $returnUrl);
        $this->assertStringNotContainsString('reseller-topup', $returnUrl);
    }

    public function test_token_sudah_terbit_sebelum_return_url_dibangun(): void
    {
        // Kalau token diterbitkan malas SETELAH returnUrl dibuat, pembeli bisa
        // kembali dari pembayaran ke tautan yang belum punya token → 404 tepat
        // di momen paling penting. Di sini foreign key-nya dibuktikan lewat DB.
        $this->daftar('b2token@example.test', 'tokob2token')->assertCreated();

        $returnUrl = (string) end(self::$duitkuParams)['returnUrl'];
        $invoice = SubscriptionInvoice::query()->firstOrFail();

        $this->assertNotNull($invoice->public_token, 'Token harus sudah ada DI DB saat returnUrl dibangun.');
        $this->assertStringContainsString((string) $invoice->public_token, $returnUrl);

        // Dan tautannya benar-benar bisa dibuka.
        $this->get($returnUrl)->assertOk();
    }

    public function test_invoice_lunas_tanpa_token_yang_pernah_dibayar_akan_404(): void
    {
        // Ini alasan B2 harus menerbitkan token: invoice yang lunas SEBELUM kolom
        // token diisi tidak pernah punya tautan sukses. Membuktikan bahwa keadaan
        // itu memang mungkin — jadi penerbitan di createInvoice bukan hiasan.
        [$tenant, $invoice] = $this->makeInvoice(['status' => SubscriptionInvoice::STATUS_PAID]);

        $this->assertNull($invoice->public_token);
        $this->get(self::BASE . '/tagihan/' . str_repeat('a', 64) . '/sukses')->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // B3 / S4: halaman sukses
    // ---------------------------------------------------------------------

    public function test_halaman_sukses_tampil_tanpa_login(): void
    {
        [$tenant, $invoice] = $this->makeInvoice([
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token . '/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TenantBillingSuccess')
                ->where('state', 'paid')
                ->where('invoice.number', 'INV-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT))
            );
    }

    public function test_invoice_lunas_menampilkan_store_dan_periode(): void
    {
        [$tenant, $invoice] = $this->makeInvoice([
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token . '/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('state', 'paid')
                ->where('store.name', 'Toko Sukses')
                ->where('store.host', 'toko-sukses-uji.' . parse_url(self::BASE, PHP_URL_HOST))
                ->where('store.url', 'https://toko-sukses-uji.' . parse_url(self::BASE, PHP_URL_HOST))
            );
    }

    public function test_belum_terkonfirmasi_ditampilkan_netral_bukan_sukses(): void
    {
        // Pembeli bisa tiba SEBELUM callback Duitku masuk. Halaman ini tidak boleh
        // bilang "berhasil" (status belum resmi) maupun "gagal".
        [$tenant, $invoice] = $this->makeInvoice(['status' => SubscriptionInvoice::STATUS_PENDING]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token . '/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TenantBillingSuccess')
                ->where('state', 'confirming')
            );
    }

    public function test_status_tak_dikenal_tidak_pernah_dianggap_sukses(): void
    {
        [$tenant, $invoice] = $this->makeInvoice(['status' => 'entah-apa']);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token . '/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('state', 'confirming'));
    }

    public function test_kedaluwarsa_mengarahkan_kembali_ke_halaman_tagihan(): void
    {
        [$tenant, $invoice] = $this->makeInvoice(['status' => SubscriptionInvoice::STATUS_EXPIRED]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token . '/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('state', 'expired')
                ->where('billingUrl', self::BASE . '/tagihan/' . $token)
            );
    }

    public function test_dibatalkan_ditampilkan_netral(): void
    {
        [$tenant, $invoice] = $this->makeInvoice(['status' => SubscriptionInvoice::STATUS_CANCELLED]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token . '/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('state', 'cancelled'));
    }

    public function test_token_tidak_dikenal_menghasilkan_404(): void
    {
        $this->makeInvoice();

        $this->get(self::BASE . '/tagihan/' . str_repeat('b', 64) . '/sukses')->assertNotFound();
    }

    public function test_token_berformat_salah_tidak_sampai_ke_controller(): void
    {
        $this->makeInvoice();

        foreach (['abc', str_repeat('b', 200), 'bukan-token!', '../../etc/passwd'] as $cacat) {
            $this->assertNotSame(
                200,
                $this->get(self::BASE . '/tagihan/' . $cacat . '/sukses')->getStatusCode(),
                "Token cacat ('{$cacat}') tidak boleh menghasilkan halaman sukses."
            );
        }
    }

    public function test_subdomain_tenant_tidak_boleh_menyajikan_halaman_sukses(): void
    {
        [$tenant, $invoice] = $this->makeInvoice([
            'status' => SubscriptionInvoice::STATUS_PAID,
        ]);

        $token = $invoice->ensurePublicToken();

        $this->get('https://' . $tenant->subdomain . '.' . parse_url(self::BASE, PHP_URL_HOST) . '/tagihan/' . $token . '/sukses')
            ->assertNotFound();
    }

    public function test_halaman_sukses_noindex_dan_tanpa_canonical(): void
    {
        [$tenant, $invoice] = $this->makeInvoice([
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token . '/sukses')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.robots', 'noindex, nofollow')
                ->where('meta.canonical', '')
            );
    }

    public function test_halaman_sukses_tidak_membocorkan_identitas_pribadi_owner(): void
    {
        [$tenant, $invoice] = $this->makeInvoice([
            'status' => SubscriptionInvoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        $token = $invoice->ensurePublicToken();

        $response = $this->get(self::BASE . '/tagihan/' . $token . '/sukses')->assertOk();
        $props = json_encode($response->viewData('page')['props'], JSON_THROW_ON_ERROR);

        // Toko milik pembeli sendiri boleh tampil; identitas pribadinya tidak.
        // Catatan: nomor di `contactUrl` adalah kontak PLATFORM (dari
        // `setting_webs.url_wa`), bukan nomor owner — jadi yang diuji di sini
        // adalah nomor owner itu sendiri.
        foreach (['Rahasia Sekali', 'owner-sukses@example.test', '628999888777', 'siteConfig', 'seoDefaults'] as $bocor) {
            $this->assertStringNotContainsString($bocor, $props, "Props halaman sukses membocorkan '{$bocor}'.");
        }

        $this->assertStringContainsString('Toko Sukses', $props, 'Nama toko justru harus tampil — itu yang dia beli.');
    }

    public function test_rute_sukses_punya_guard_host_dan_limiter(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getName() === 'billing.public.success');

        $this->assertNotNull($route, 'Route billing.public.success tidak ditemukan.');

        $middleware = $route->gatherMiddleware();

        $this->assertContains('billing.platform_host', $middleware, 'Halaman sukses hanya boleh hidup di host platform.');
        $this->assertContains('throttle:public-billing', $middleware, 'Halaman sukses wajib dibatasi limiter sendiri.');
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    private function daftar(string $email, string $subdomain, string $password = 'password123')
    {
        return $this->postJson('/api/tenant/register', [
            'name' => 'Uji Sukses',
            'email' => $email,
            'password' => $password,
            'no_wa' => '081234567890',
            'store_name' => 'Uji Sukses',
            'subdomain' => $subdomain,
            'tier' => 'starter',
            'terms_accepted' => true,
        ]);
    }

    private function makeInvoice(array $columns = []): array
    {
        $owner = User::factory()->create([
            'name' => 'Rahasia Sekali',
            'role' => 'Gold',
            'email' => 'owner-sukses@example.test',
            'no_wa' => '0812 999 888 777',
        ]);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Sukses',
            'subdomain' => 'toko-sukses-uji',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-SUKSES-' . strtoupper(uniqid()),
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $invoice = SubscriptionInvoice::query()->create(array_replace([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDay(),
            'metadata' => ['source' => 'tenant_self_registration'],
        ], $columns));

        return [$tenant, $invoice];
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
                // Rekam parameter NYATA yang dikirim, supaya B2 bisa diuji dari
                // payload yang benar-benar keluar — bukan dari kode yang dibaca.
                PublicBillingSuccessPageTest::recordDuitkuParams($params);

                return [
                    'statusCode' => '00',
                    'statusMessage' => 'SUCCESS',
                    'reference' => 'DUITKU-REF-' . ($params['merchantOrderId'] ?? 'X'),
                    'paymentUrl' => 'https://sandbox.duitku.test/pay/' . ($params['merchantOrderId'] ?? 'X'),
                    'amount' => $params['paymentAmount'],
                ];
            }
        });
    }

    public static function recordDuitkuParams(array $params): void
    {
        self::$duitkuParams[] = $params;
    }
}
