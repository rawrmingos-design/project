<?php

namespace Tests\Feature\Tenancy;

use App\Models\SettingWeb;
use App\Models\Subscription;
use App\Models\SubscriptionInvoice;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Payments\DuitkuPopClient;
use App\Tenancy\Contracts\CloudflareDnsClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Task 2 — "satu sumber kebenaran" untuk pengecekan nama subdomain:
 * endpoint cek dan jalur pendaftaran HARUS selalu sepakat.
 */
class TenantRegistrationGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);

        // Throttle dimatikan agar test deterministik (limiter bisa menumpuk
        // antar-test dalam satu proses). Konfigurasi throttle sendiri dikunci
        // oleh test_kuota_throttle_endpoint_cek_dinaikkan().
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        // Klien Cloudflare palsu: tidak pernah menyentuh jaringan.
        $this->app->instance(CloudflareDnsClientInterface::class, new class implements CloudflareDnsClientInterface
        {
            public function hostnamesUnder(string $zoneId, string $suffix): array
            {
                return [];
            }
        });

        $this->fakeDuitkuInvoice();
    }

    public function test_endpoint_cek_mengembalikan_alasan_untuk_nama_reserved(): void
    {
        $reserved = $this->getJson('/api/subdomain/check?name=webmail')->assertOk();

        $reserved->assertJsonPath('available', false);
        $this->assertNotEmpty(
            $reserved->json('reason'),
            'Endpoint cek harus mengembalikan reason supaya UI bisa menampilkan pesan yang jelas.'
        );

        $ok = $this->getJson('/api/subdomain/check?name=egymarket')->assertOk();

        $ok->assertJsonPath('available', true);
        $this->assertNull($ok->json('reason'));
    }

    public function test_endpoint_cek_dan_register_selalu_sepakat(): void
    {
        $nama = ['egymarket', 'webmail', 'tokoku', 'store-a', 'admin', 'ns1', 'notion'];

        foreach ($nama as $i => $n) {
            $tersedia = (bool) $this->getJson("/api/subdomain/check?name={$n}")->json('available');

            $response = $this->postJson('/api/tenant/register', [
                'name' => 'Uji Sepakat',
                'email' => "sepakat{$i}@example.test",
                'password' => 'password123',
                'no_wa' => '081234567890',
                'store_name' => 'Uji Sepakat',
                'subdomain' => $n,
                'tier' => 'starter',
                'terms_accepted' => true,
            ]);

            $terdaftar = in_array($response->status(), [200, 201], true);

            $this->assertSame(
                $tersedia,
                $terdaftar,
                "Endpoint cek bilang '{$n}' = " . var_export($tersedia, true)
                    . " tapi register memberi status {$response->status()}. Keduanya harus sepakat."
            );
        }
    }

    public function test_register_menolak_nama_reserved_dengan_alasan_yang_bisa_dibaca_user(): void
    {
        $response = $this->postJson('/api/tenant/register', [
            'name' => 'Uji Reserved',
            'email' => 'reserved@example.test',
            'password' => 'password123',
            'no_wa' => '081234567890',
            'store_name' => 'Uji Reserved',
            'subdomain' => 'webmail',
            'tier' => 'starter',
            'terms_accepted' => true,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('subdomain');

        $pesan = (string) $response->json('errors.subdomain.0');
        $this->assertNotEmpty($pesan);
        $this->assertStringNotContainsString(
            'webmail',
            strtolower($pesan) === 'webmail' ? $pesan : '',
            'Pesan tidak boleh cuma mengulang nama.'
        );

        $this->assertDatabaseMissing('tenants', ['subdomain' => 'webmail']);
        $this->assertDatabaseMissing('users', ['email' => 'reserved@example.test']);
    }

    public function test_resume_memakai_ulang_tenant_dan_invoice_lama(): void
    {
        $pertama = $this->daftar('resume@example.test', 'tokoku');
        $pertama->assertCreated();

        $tenantId = $pertama->json('tenant.id');
        $invoiceId = $pertama->json('invoice.id');

        $kedua = $this->daftar('resume@example.test', 'tokoku');

        // Resume: tidak boleh menumpuk user/tenant/invoice baru.
        $kedua->assertOk();
        $this->assertSame($tenantId, $kedua->json('tenant.id'));
        $this->assertSame($invoiceId, $kedua->json('invoice.id'));
        $this->assertTrue((bool) $kedua->json('resumed'));

        $this->assertSame(1, User::query()->where('email', 'resume@example.test')->count());
        $this->assertSame(1, Tenant::query()->where('subdomain', 'tokoku')->count());
        $this->assertSame(1, SubscriptionInvoice::query()->count());
    }

    public function test_resume_bisa_mengganti_subdomain_dan_username_ikut_berubah(): void
    {
        $this->daftar('ganti@example.test', 'tokoku')->assertCreated();

        $owner = User::query()->where('email', 'ganti@example.test')->firstOrFail();
        $usernameLama = $owner->username;

        $this->daftar('ganti@example.test', 'tokosaya')->assertOk();

        $owner->refresh();
        $tenant = Tenant::query()->where('owner_user_id', $owner->id)->firstOrFail();

        $this->assertSame('tokosaya', $tenant->subdomain);
        $this->assertNotSame($usernameLama, $owner->username);
        $this->assertStringContainsString('tokosaya', $owner->username);
    }

    public function test_resume_menolak_password_yang_tidak_cocok(): void
    {
        $this->daftar('aman@example.test', 'tokoku')->assertCreated();

        $response = $this->daftar('aman@example.test', 'tokosaya', password: 'password-beda');

        // Email yang sudah terdaftar TIDAK boleh bisa diambil alih hanya
        // dengan mengetahui alamat emailnya.
        $response->assertStatus(422)->assertJsonValidationErrors('password');

        $tenant = Tenant::query()->firstOrFail();
        $this->assertSame('tokoku', $tenant->subdomain, 'Subdomain tidak boleh berubah tanpa password yang benar.');
    }

    public function test_register_ditolak_kalau_tenant_sudah_aktif(): void
    {
        $owner = User::factory()->create([
            'role' => 'Member',
            'email' => 'aktif@example.test',
            'password' => Hash::make('password123'),
        ]);
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Aktif',
            'subdomain' => 'tokoaktif',
            'tier' => 'starter',
            'status' => Tenant::STATUS_ACTIVE,
        ]);
        $owner->forceFill(['tenant_id' => $tenant->id])->save();

        $response = $this->daftar('aktif@example.test', 'tokoku');

        $response->assertStatus(422);
        $this->assertSame(1, Tenant::query()->count(), 'Tidak boleh membuat tenant kedua.');
        $this->assertSame(1, User::query()->where('email', 'aktif@example.test')->count());
    }

    public function test_invoice_lama_dipakai_ulang_meski_sudah_kedaluwarsa(): void
    {
        $pertama = $this->daftar('kadaluarsa@example.test', 'tokoku');
        $pertama->assertCreated();

        $invoiceLama = SubscriptionInvoice::query()->firstOrFail();
        $invoiceLama->forceFill(['status' => SubscriptionInvoice::STATUS_EXPIRED])->save();

        $kedua = $this->daftar('kadaluarsa@example.test', 'tokoku');

        $kedua->assertOk();

        // Q8=A: satu langganan = satu gateway_ref (merchantOrderId Duitku).
        // Invoice lama DIPAKAI ULANG (direset ke pending), bukan ditambah.
        $this->assertSame($pertama->json('invoice.id'), $kedua->json('invoice.id'));
        $this->assertSame(1, SubscriptionInvoice::query()->count());
        $this->assertSame(
            SubscriptionInvoice::STATUS_PENDING,
            SubscriptionInvoice::query()->firstOrFail()->status
        );
    }

    public function test_kuota_throttle_endpoint_cek_dinaikkan(): void
    {
        $routes = app('router')->getRoutes();
        $route = null;
        foreach ($routes as $candidate) {
            if ($candidate->uri() === 'api/subdomain/check') {
                $route = $candidate;
                break;
            }
        }

        $this->assertNotNull($route, 'Route cek subdomain tidak ditemukan.');

        $throttle = collect($route->middleware())->first(fn ($m) => str_starts_with((string) $m, 'throttle:'));
        $this->assertNotNull($throttle, 'Endpoint cek harus tetap punya throttle.');

        [$batas] = explode(',', explode(':', $throttle, 2)[1]);
        $this->assertGreaterThanOrEqual(
            60,
            (int) $batas,
            'Setelah debounce dibuang, throttle harus dinaikkan (>= 60/menit).'
        );
    }

    // -----------------------------------------------------------------------

    private function daftar(string $email, string $subdomain, string $password = 'password123')
    {
        return $this->postJson('/api/tenant/register', [
            'name' => 'Uji Tenant',
            'email' => $email,
            'password' => $password,
            'no_wa' => '081234567890',
            'store_name' => 'Uji Tenant',
            'subdomain' => $subdomain,
            'tier' => 'starter',
            'terms_accepted' => true,
        ]);
    }

    private function fakeDuitkuInvoice(): SettingWeb
    {
        $settings = SettingWeb::query()->firstOrCreate(['id' => 1], [
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
                    'reference' => 'DUITKU-REF-' . ($params['merchantOrderId'] ?? 'X'),
                    'paymentUrl' => 'https://sandbox.duitku.test/pay/' . ($params['merchantOrderId'] ?? 'X'),
                    'amount' => $params['paymentAmount'],
                ];
            }
        });

        return $settings;
    }
}
