<?php

namespace Tests\Unit\Tenancy;

use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\DuitkuPopClient;
use App\Tenancy\DuitkuSubscriptionPaymentService;
use App\Tenancy\SubscriptionPaymentLinkResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * D4: tautan bayar diregenerasi saat kedaluwarsa — tanpa mengubah tautan yang
 * sudah terkirim ke owner.
 */
class SubscriptionPaymentLinkResolverTest extends TestCase
{
    use RefreshDatabase;

    private int $createInvoiceCalls = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeDuitku();
    }

    // ---------------------------------------------------------------------
    // Tautan masih hidup: gateway TIDAK dipanggil
    // ---------------------------------------------------------------------

    public function test_tautan_hidup_dikembalikan_tanpa_memanggil_gateway(): void
    {
        $invoice = $this->makeInvoice([], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/masih-hidup',
                'expires_at' => now()->addHours(6)->toIso8601String(),
            ],
        ]);

        $url = app(SubscriptionPaymentLinkResolver::class)->resolve($invoice);

        $this->assertSame('https://sandbox.duitku.test/pay/masih-hidup', $url);
        $this->assertSame(0, $this->createInvoiceCalls, 'Gateway tidak boleh dipanggil kalau tautan masih hidup.');
    }

    public function test_halaman_dibuka_berkali_kali_tidak_menagih_gateway(): void
    {
        // Owner yang menunggu pembayaran akan me-refresh halaman. Setiap
        // pemanggilan gateway berarti kuota terpakai dan tautan diperpendek.
        $invoice = $this->makeInvoice([], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/refresh',
                'expires_at' => now()->addHours(6)->toIso8601String(),
            ],
        ]);

        $resolver = app(SubscriptionPaymentLinkResolver::class);

        for ($i = 0; $i < 5; $i++) {
            $this->assertSame('https://sandbox.duitku.test/pay/refresh', $resolver->resolve($invoice->fresh()));
        }

        $this->assertSame(0, $this->createInvoiceCalls);
    }

    // ---------------------------------------------------------------------
    // Tautan mati: diregenerasi, identitas invoice TIDAK berubah
    // ---------------------------------------------------------------------

    public function test_tautan_mati_diregenerasi_lewat_jalur_yang_sudah_ada(): void
    {
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(2)], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/kadaluwarsa',
                'expires_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $url = app(SubscriptionPaymentLinkResolver::class)->resolve($invoice);

        $this->assertSame(1, $this->createInvoiceCalls, 'Tautan mati harus membuat tautan baru.');
        $this->assertNotNull($url);
        $this->assertStringContainsString($invoice->gateway_ref, (string) $url);

        $invoice->refresh();

        $this->assertSame(
            'https://sandbox.duitku.test/pay/masih-hidup?ref=' . $invoice->gateway_ref,
            data_get($invoice->metadata, 'duitku.payment_url')
        );
        $this->assertTrue(
            \Illuminate\Support\Carbon::parse(data_get($invoice->metadata, 'duitku.expires_at'))->isFuture(),
            'expires_at harus diperbarui, bukan dibiarkan lampau.'
        );
    }

    public function test_regenerasi_tidak_mengubah_gateway_ref_maupun_public_token(): void
    {
        // Ini inti Task 4: tautan yang sudah dikirim ke owner (dan `gateway_ref`
        // yang dipakai webhook Duitku untuk mencari invoice) harus tetap sama.
        // Kalau `gateway_ref` berputar, pembayaran dari tautan lama hilang.
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(2)], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/lama',
                'expires_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $refSemula = $invoice->gateway_ref;
        $tokenSemula = $invoice->ensurePublicToken();
        $idSemula = $invoice->id;

        app(SubscriptionPaymentLinkResolver::class)->resolve($invoice);
        $invoice->refresh();

        $this->assertSame($refSemula, $invoice->gateway_ref, 'gateway_ref tidak boleh berubah.');
        $this->assertSame($tokenSemula, $invoice->public_token, 'public_token tidak boleh berubah.');
        $this->assertSame($idSemula, $invoice->id, 'Regenerasi memperbarui invoice yang sama, bukan membuat baris baru.');
        $this->assertSame(
            1,
            SubscriptionInvoice::query()->where('subscription_id', $invoice->subscription_id)->count(),
            'Tidak boleh ada invoice tambahan.'
        );
    }

    public function test_tautan_tanpa_expires_at_dianggap_mati(): void
    {
        // Jangan pernah menganggap tautan hidup tanpa dasar. Tanpa keterangan
        // kedaluwarsa, satu-satunya dasar yang tersisa adalah jatuh tempo — dan
        // di sini jatuh temponya sudah lewat.
        $invoice = $this->makeInvoice(['due_date' => now()->subDay()], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/tanpa-expiry',
            ],
        ]);

        app(SubscriptionPaymentLinkResolver::class)->resolve($invoice);

        $this->assertSame(1, $this->createInvoiceCalls, 'Tautan tanpa dasar hidup harus dianggap mati.');
    }

    // ---------------------------------------------------------------------
    // Tidak ada yang perlu dibayar
    // ---------------------------------------------------------------------

    public function test_invoice_lunas_tidak_memanggil_gateway_dan_tidak_ada_tautan(): void
    {
        $invoice = $this->makeInvoice(['status' => SubscriptionInvoice::STATUS_PAID], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/lunas',
                'expires_at' => now()->addHours(6)->toIso8601String(),
            ],
        ]);

        $this->assertNull(app(SubscriptionPaymentLinkResolver::class)->resolve($invoice));
        $this->assertSame(0, $this->createInvoiceCalls);
    }

    public function test_invoice_lunas_dengan_tautan_mati_tidak_membuat_tautan_baru(): void
    {
        $invoice = $this->makeInvoice([
            'status' => SubscriptionInvoice::STATUS_PAID,
            'due_date' => now()->subDays(3),
        ], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/lunas-lama',
                'expires_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $this->assertNull(app(SubscriptionPaymentLinkResolver::class)->resolve($invoice));
        $this->assertSame(0, $this->createInvoiceCalls, 'Invoice lunas tidak boleh ditagihkan ulang ke Duitku.');
    }

    public function test_nominal_nol_tidak_membuat_tautan(): void
    {
        $invoice = $this->makeInvoice(['amount' => 0, 'due_date' => now()->subDay()], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/nol',
                'expires_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        $this->assertNull(app(SubscriptionPaymentLinkResolver::class)->resolve($invoice));
        $this->assertSame(0, $this->createInvoiceCalls);
    }

    // ---------------------------------------------------------------------
    // Gateway bermasalah: halaman tetap tampil
    // ---------------------------------------------------------------------

    public function test_gateway_melempar_error_tidak_sampai_ke_pengunjung(): void
    {
        Log::spy();

        $this->app->instance(DuitkuPopClient::class, new class extends DuitkuPopClient
        {
            public function createInvoice(array $params, \Duitku\Config $config): array
            {
                throw new \RuntimeException('Duitku sedang gangguan.');
            }
        });

        $invoice = $this->makeInvoice(['due_date' => now()->subDay()], [
            'duitku' => [
                'payment_url' => 'https://sandbox.duitku.test/pay/mati',
                'expires_at' => now()->subDay()->toIso8601String(),
            ],
        ]);

        // Tidak melempar — halaman tagihan tetap tampil, hanya tombolnya absen.
        $this->assertNull(app(SubscriptionPaymentLinkResolver::class)->resolve($invoice));

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $pesan, array $konteks = []) => str_contains($pesan, 'regenerasi tautan bayar')
                && ($konteks['invoice_id'] ?? null) === $invoice->id)
            ->once();
    }

    // ---------------------------------------------------------------------
    // Helper
    // ---------------------------------------------------------------------

    /**
     * @param  array  $columns  Kolom invoice (status, due_date, amount, …).
     * @param  array  $metadata Digabung dangkal ke `metadata`.
     */
    private function makeInvoice(array $columns = [], array $metadata = []): SubscriptionInvoice
    {
        $owner = User::factory()->create(['role' => 'Gold']);

        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Tautan',
            'subdomain' => 'toko-tautan-' . uniqid(),
            'tier' => 'starter',
            'status' => Tenant::STATUS_SUSPENDED,
        ]);

        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'tier' => 'starter',
            'price' => 500000,
            'status' => Subscription::STATUS_ACTIVE,
            'gateway_ref' => 'SUB-LINK-' . strtoupper(uniqid()),
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addDays(5),
        ]);

        return SubscriptionInvoice::query()->create(array_replace([
            'subscription_id' => $subscription->id,
            'amount' => 500000,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'gateway' => 'duitku',
            'gateway_ref' => SubscriptionInvoice::freshGatewayRef(),
            'due_date' => now()->addDays(2),
            'metadata' => array_replace([
                'source' => 'billing_renewal',
                'kind' => 'renewal',
            ], $metadata),
        ], $columns));
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

        $this->createInvoiceCalls = 0;

        $this->app->instance(DuitkuPopClient::class, new class($this) extends DuitkuPopClient
        {
            public function __construct(private SubscriptionPaymentLinkResolverTest $test) {}

            public function createInvoice(array $params, \Duitku\Config $config): array
            {
                $this->test->countCreateInvoice();

                return [
                    'statusCode' => '00',
                    'statusMessage' => 'SUCCESS',
                    'reference' => 'DUITKU-REF-' . substr(md5($params['merchantOrderId']), 0, 8),
                    // Tautan baru memuat merchantOrderId, jadi test bisa
                    // membuktikan ref-nya memang tidak berputar.
                    'paymentUrl' => 'https://sandbox.duitku.test/pay/masih-hidup?ref=' . $params['merchantOrderId'],
                    'amount' => $params['paymentAmount'],
                ];
            }
        });
    }

    public function countCreateInvoice(): void
    {
        $this->createInvoiceCalls++;
    }
}
