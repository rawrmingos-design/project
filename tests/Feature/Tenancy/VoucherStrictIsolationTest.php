<?php

namespace Tests\Feature\Tenancy;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Voucher\VoucherResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Isolasi KETAT kupon: kupon hanya berlaku di tempat ia dibuat.
 *
 * Aturan yang dijaga:
 *   - kupon platform (tenant_id NULL) hanya di situs platform
 *   - kupon tenant hanya di toko tenant itu
 *   - kode yang sama boleh hidup berdampingan di platform dan tenant,
 *     karena cakupannya tidak pernah bertemu
 *   - tenant dihapus -> kuponnya ikut mati, TIDAK berubah jadi kupon platform
 *
 * Poin terakhir itu yang paling berbahaya: FK `nullOnDelete` membuat kupon
 * milik tenant yang dihapus berubah menjadi kupon platform, sehingga promo yang
 * dulu hanya untuk satu toko tiba-tiba berlaku di seluruh platform — dan yang
 * menanggungnya platform.
 *
 * Alasan aturan ini dipilih (bukan "tenant selalu menanggung diskon"): kalau
 * kupon platform boleh dipakai di toko tenant, promo platform memotong margin
 * tenant yang tidak pernah menyetujuinya, dan order jadi ditolak saat promo
 * melewati lantai modal tenant. Isolasi menghapus pertanyaan itu sepenuhnya.
 */
class VoucherStrictIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private Layanan $service;
    private User $pembeliA;
    private User $pembeliB;
    private VoucherResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'tenancy.disabled' => false,
            'app.url' => 'https://topupengine.test',
        ]);

        $this->mockSuccessfulAccountValidation();
        Http::fake(['ipinfo.io/*' => Http::response(['ip' => '127.0.0.1'], 200)]);

        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        app(\App\Tenancy\TierPricingResolver::class)->flush();

        $this->resolver = app(VoucherResolver::class);

        SettingWeb::query()->create([
            'judul_web' => 'Platform Uji',
            'deskripsi_web' => 'Deskripsi',
            'keywords' => 'uji',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/uji',
            'url_tiktok' => 'https://tiktok.com/@uji',
            'url_youtube' => 'https://youtube.com/uji',
            'url_fb' => 'https://facebook.com/uji',
            'topupindo_api' => 'topupindo',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'order_prefik' => 'INV',
            'paydisini_apikey' => 'key',
        ]);

        $this->tenantA = $this->makeTenant('toko-a');
        $this->tenantB = $this->makeTenant('toko-b');

        $this->pembeliA = $this->makePembeli($this->tenantA, 'pembeli-a');
        $this->pembeliB = $this->makePembeli($this->tenantB, 'pembeli-b');

        $kategori = Kategori::factory()->create(['tipe' => 'game', 'server_id' => 1]);

        $this->service = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'provider' => 'manual',
            'provider_id' => 'manual-sku',
            'harga' => 100000,
            'harga_member' => 100000,
            'harga_platinum' => 100000,
            'harga_gold' => 100000,
            'profit_member' => 5,
            'profit_platinum' => 5,
            'profit_gold' => 5,
            'catatan' => 'Uji',
            'status' => 'available',
        ]);

        Method::create([
            'name' => 'Saldo',
            'code' => 'SALDO',
            'tipe' => 'saldo',
            'payment' => 'saldo',
            'images' => 'saldo.png',
            'keterangan' => 'Bayar pakai saldo',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);
    }

    private function makeTenant(string $subdomain): Tenant
    {
        $owner = User::factory()->create(['role' => 'tenant_owner']);

        return Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko ' . $subdomain,
            'subdomain' => $subdomain,
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 5],
        ]);
    }

    private function makePembeli(Tenant $tenant, string $username): User
    {
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'username' => $username,
            'role' => 'Member',
            'balance' => 1000000,
            'no_wa' => '081234567890',
        ]);
    }

    private function makeVoucher(?int $tenantId, string $kode, array $attributes = []): Voucher
    {
        return Voucher::create(array_merge([
            'tenant_id' => $tenantId,
            'kode' => $kode,
            'promo' => 1.5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ], $attributes));
    }

    private function checkout(Tenant $tenant, User $pembeli, ?string $voucher = null)
    {
        $payload = [
            'uid' => '12345',
            'zone' => '1234',
            'service' => $this->service->id,
            'payment_method' => 'SALDO',
            'nomor' => '081234567890',
            'ktg_tipe' => 'game',
        ];

        if ($voucher !== null) {
            $payload['voucher'] = $voucher;
        }

        return $this->actingAs($pembeli)->postJson(
            "https://{$tenant->subdomain}.topupengine.test/order/checkout",
            $payload
        );
    }

    public function test_kupon_platform_tidak_bisa_dipakai_di_toko_tenant(): void
    {
        $this->makeVoucher(null, 'PROMO-PLATFORM');

        $this->assertNull(
            $this->resolver->resolve($this->tenantA, 'PROMO-PLATFORM'),
            'kupon platform tidak boleh ditemukan di toko tenant'
        );

        $sebelum = Pembelian::query()->count();

        $this->checkout($this->tenantA, $this->pembeliA, 'PROMO-PLATFORM')
            ->assertOk()
            ->assertJson(['status' => false]);

        $this->assertSame($sebelum, Pembelian::query()->count(), 'tidak ada order dibuat');
    }

    public function test_kupon_tenant_tidak_bisa_dipakai_di_situs_platform(): void
    {
        $this->makeVoucher($this->tenantA->id, 'PROMO-TOKO');

        $this->assertNull(
            $this->resolver->resolve(null, 'PROMO-TOKO'),
            'kupon tenant tidak boleh ditemukan di situs platform'
        );
    }

    public function test_kupon_tenant_a_tidak_bisa_dipakai_di_toko_b(): void
    {
        $this->makeVoucher($this->tenantA->id, 'PROMO-A');

        $this->assertNull($this->resolver->resolve($this->tenantB, 'PROMO-A'));

        $sebelum = Pembelian::query()->count();

        $this->checkout($this->tenantB, $this->pembeliB, 'PROMO-A')
            ->assertOk()
            ->assertJson(['status' => false]);

        $this->assertSame($sebelum, Pembelian::query()->count(), 'tidak ada order dibuat di toko B');
    }

    public function test_kode_sama_boleh_hidup_di_platform_dan_tenant(): void
    {
        $milikPlatform = $this->makeVoucher(null, 'PROMO10', ['promo' => 20]);
        $milikTenant = $this->makeVoucher($this->tenantA->id, 'PROMO10', ['promo' => 3]);

        // Cakupannya tidak pernah bertemu, jadi masing-masing dapat versinya sendiri.
        $diPlatform = $this->resolver->resolve(null, 'PROMO10');
        $diTenant = $this->resolver->resolve($this->tenantA, 'PROMO10');

        $this->assertSame($milikPlatform->id, $diPlatform?->id);
        $this->assertSame(20.0, (float) $diPlatform?->promo);

        $this->assertSame($milikTenant->id, $diTenant?->id);
        $this->assertSame(3.0, (float) $diTenant?->promo);
    }

    public function test_kupon_tenant_hilang_saat_tenant_dihapus(): void
    {
        $milikTenant = $this->makeVoucher($this->tenantA->id, 'IKUT-MATI');
        $milikPlatform = $this->makeVoucher(null, 'TETAP');

        $this->tenantA->delete();

        // Bukan berubah jadi kupon platform — itu akan membuat promo satu toko
        // berlaku di seluruh platform dan platform yang menanggungnya.
        $this->assertNull(
            $milikTenant->fresh(),
            'kupon tenant harus ikut mati, bukan naik jadi kupon platform'
        );

        $this->assertNotNull($milikPlatform->fresh(), 'kupon platform tidak tersentuh');
        $this->assertNull($this->resolver->resolve(null, 'IKUT-MATI'));
    }

    public function test_daftar_kupon_toko_hanya_berisi_kupon_toko_itu(): void
    {
        $this->makeVoucher(null, 'PLATFORM-1');
        $milikA = $this->makeVoucher($this->tenantA->id, 'TOKO-A-1');
        $this->makeVoucher($this->tenantB->id, 'TOKO-B-1');

        $terlihat = Voucher::query()->ownedBy($this->tenantA->id)->pluck('kode')->all();

        $this->assertSame([$milikA->kode], $terlihat);
    }
}
