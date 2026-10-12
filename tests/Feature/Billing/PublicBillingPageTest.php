<?php

namespace Tests\Feature\Billing;

use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * D13: halaman tagihan publik ber-token di host platform.
 *
 * Konvensi: `config('app.url')` = host platform; subdomain di bawahnya dikenali
 * ResolveTenant sebagai tenant.
 */
class PublicBillingPageTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://topupengine.test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => self::BASE,
            'tenancy.disabled' => false,
            'billing.renewal_enabled' => true,
        ]);
    }

    // ---------------------------------------------------------------------
    // Bahagia: token benar → halaman tampil
    // ---------------------------------------------------------------------

    public function test_token_valid_menampilkan_halaman_tagihan_tanpa_login(): void
    {
        [$tenant, $invoice] = $this->makeInvoice();
        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/TenantBilling')
                ->where('invoice.amount', 500000)
                ->where('invoice.status', SubscriptionInvoice::STATUS_PENDING)
                ->where('invoice.number', 'INV-' . str_pad((string) $invoice->id, 6, '0', STR_PAD_LEFT))
            );
    }

    public function test_tautan_bayar_muncul_saat_masih_hidup(): void
    {
        [, $invoice] = $this->makeInvoice([], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/hidup',
                'expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('payUrl', 'https://sandbox.duitku.test/pay/hidup')
            );
    }

    public function test_tautan_bayar_tidak_muncul_saat_sudah_kedaluwarsa(): void
    {
        // Tautan gateway hanya hidup 1440 menit. Setelah lewat, jangan pernah
        // menampilkan tombol yang akan gagal saat diklik.
        [, $invoice] = $this->makeInvoice(['due_date' => now()->subDays(2)], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/mati',
                'expires_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('payUrl', null));
    }

    public function test_tautan_bayar_tidak_muncul_untuk_invoice_lunas(): void
    {
        [, $invoice] = $this->makeInvoice(['status' => SubscriptionInvoice::STATUS_PAID], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/lunas',
                'expires_at' => now()->addHour()->toIso8601String(),
            ],
        ]);

        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('payUrl', null));
    }

    // ---------------------------------------------------------------------
    // D13.4: tidak ada identitas pelanggan / merek tenant
    // ---------------------------------------------------------------------

    public function test_props_tidak_membocorkan_identitas_pelanggan_atau_merek_tenant(): void
    {
        [$tenant, $invoice] = $this->makeInvoice();
        $token = $invoice->ensurePublicToken();

        $response = $this->get(self::BASE . '/tagihan/' . $token)->assertOk();

        $props = json_encode($response->viewData('page')['props'], JSON_THROW_ON_ERROR);

        foreach ([
            'Rahasia Sekali',                    // nama owner
            'owner-billing@example.test',        // email owner
            '628123456789',                      // nomor WA
            'do-sunt-elit-ut-hi',                // subdomain penuh
            'siteConfig',
            'seoDefaults',
        ] as $bocor) {
            $this->assertStringNotContainsString(
                $bocor,
                $props,
                "Props halaman tagihan membocorkan '{$bocor}'."
            );
        }

        // Yang boleh tampil: label tenant yang disensor.
        $this->assertStringContainsString('d****************i', $props);
    }

    public function test_alamat_yang_disensor_hanya_label_tenant(): void
    {
        [, $invoice] = $this->makeInvoice(subdomain: 'istana');
        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('address', 'i****a'));
    }

    // ---------------------------------------------------------------------
    // Q5: kontak kosong → label netral
    // ---------------------------------------------------------------------

    public function test_tanpa_setting_kontak_label_netral_dan_bukan_whatsapp(): void
    {
        // Tanpa baris `setting_webs`, dua sumber kontak sama-sama kosong —
        // persis Q5. Tidak ada nomor yang boleh muncul di markup.
        [, $invoice] = $this->makeInvoice();
        $token = $invoice->ensurePublicToken();

        $this->assertNull(SettingWeb::query()->find(1));

        $this->get(self::BASE . '/tagihan/' . $token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('contactIsWhatsapp', false)
                ->where('contactUrl', self::BASE)
            );
    }

    // ---------------------------------------------------------------------
    // D13.6 / token tidak berlaku
    // ---------------------------------------------------------------------

    public function test_token_tidak_dikenal_menghasilkan_404(): void
    {
        $this->makeInvoice();

        $this->get(self::BASE . '/tagihan/' . str_repeat('a', 64))
            ->assertNotFound();
    }

    public function test_token_yang_sudah_dicabut_menghasilkan_404(): void
    {
        // D13.6: token di-null saat lunas / dibatalkan. Tautan lama harus mati,
        // bukan menunjuk invoice lain.
        [, $invoice] = $this->makeInvoice();
        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)->assertOk();

        $invoice->revokePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)->assertNotFound();
    }

    public function test_token_berformat_salah_tidak_pernah_sampai_ke_controller(): void
    {
        // Pola rute menahan token yang tidak masuk akal sebelum controller
        // berjalan — halaman tagihan tidak pernah dirender untuk masukan cacat.
        $this->makeInvoice();

        foreach (['abc', str_repeat('a', 200), 'bukan-token!', '../../etc/passwd'] as $cacat) {
            $response = $this->get(self::BASE . '/tagihan/' . $cacat);

            $this->assertNotSame(
                200,
                $response->getStatusCode(),
                "Token berformat salah ('{$cacat}') tidak boleh menghasilkan halaman tagihan."
            );
        }
    }

    // ---------------------------------------------------------------------
    // D13.2: hanya host platform
    // ---------------------------------------------------------------------

    public function test_subdomain_tenant_tidak_boleh_menyajikan_halaman_tagihan(): void
    {
        // Alamat subdomain bisa ditebak. Halaman tagihan adalah halaman platform,
        // jadi di host tenant dibuat 404 — keberadaannya pun tidak dikonfirmasi.
        [, $invoice] = $this->makeInvoice();
        $token = $invoice->ensurePublicToken();

        $this->get('https://do-sunt-elit-ut-hi.topupengine.test/tagihan/' . $token)
            ->assertNotFound();
    }

    public function test_host_tak_dikenal_tidak_boleh_menyajikan_halaman_tagihan(): void
    {
        [, $invoice] = $this->makeInvoice();
        $token = $invoice->ensurePublicToken();

        $this->get('https://apa-saja.example.test/tagihan/' . $token)
            ->assertNotFound();
    }

    // ---------------------------------------------------------------------
    // D13.5: noindex
    // ---------------------------------------------------------------------

    public function test_halaman_tagihan_noindex_dan_tanpa_canonical(): void
    {
        [, $invoice] = $this->makeInvoice();
        $token = $invoice->ensurePublicToken();

        $this->get(self::BASE . '/tagihan/' . $token)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('meta.robots', 'noindex, nofollow')
                ->where('meta.canonical', '')
            );
    }

    public function test_rute_tagihan_punya_limiter_sendiri(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn ($route) => $route->getName() === 'billing.public');

        $this->assertNotNull($route, 'Route billing.public tidak ditemukan.');
        $this->assertContains(
            'throttle:public-billing',
            $route->gatherMiddleware(),
            'Halaman tagihan ber-token wajib dibatasi limiter sendiri (D13.3).'
        );
        $this->assertNotContains(
            'throttle:public-status',
            $route->gatherMiddleware(),
            'Halaman tagihan tidak boleh berbagi limiter dengan halaman status pesanan.'
        );
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /**
     * @param  array  $invoiceOverrides  Kolom invoice (status, due_date, …).
     * @param  array  $metadataOverrides Digabung dangkal ke metadata — cukup
     *                                   untuk mengganti blok `duitku`.
     * @return array{0: Tenant, 1: SubscriptionInvoice}
     */
    private function makeInvoice(array $invoiceOverrides = [], array $metadataOverrides = [], string $subdomain = 'do-sunt-elit-ut-hi'): array
    {
        $owner = User::factory()->create([
            'name' => 'Rahasia Sekali',
            'role' => 'Gold',
            'email' => 'owner-billing@example.test',
        ]);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Merek Rahasia',
            'subdomain' => $subdomain,
            'tier' => 'starter',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-BILL-' . strtoupper(uniqid()),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addDays(5),
        ]);

        $metadata = array_replace([
            'source' => 'billing_renewal',
            'kind' => 'renewal',
            'duitku' => [
                'reference' => 'DUITKU-BILL-REF',
                'payment_url' => 'https://sandbox.duitku.test/pay/bill',
                'expires_at' => now()->addHour()->toIso8601String(),
            ],
        ], $metadataOverrides);

        $invoice = SubscriptionInvoice::query()->create(array_replace([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDays(2),
            'metadata' => $metadata,
        ], $invoiceOverrides));

        return [$tenant, $invoice];
    }
}
