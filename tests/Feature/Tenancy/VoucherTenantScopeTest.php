<?php

namespace Tests\Feature\Tenancy;

use App\Models\SettingWeb;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use App\Services\Voucher\VoucherResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Isolasi voucher per tenant.
 *
 * Yang dijaga: kode buatan satu tenant tidak boleh berlaku di toko tenant lain,
 * dan voucher platform (tenant_id NULL) tetap bisa dipakai lintas toko.
 *
 * Sebelum perubahan ini, `Voucher::where('kode', ...)` tidak punya scope sama
 * sekali — satu kode buatan siapa pun berlaku di semua toko.
 */
class VoucherTenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private VoucherResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenancy.disabled' => false]);

        $this->resolver = app(VoucherResolver::class);

        $this->tenantA = $this->makeTenant('toko-a');
        $this->tenantB = $this->makeTenant('toko-b');
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
            'margin_config' => ['markup_type' => 'percent', 'markup_value' => 8],
        ]);
    }

    private function makeVoucher(?int $tenantId, string $kode, array $attributes = []): Voucher
    {
        return Voucher::create(array_merge([
            'tenant_id' => $tenantId,
            'kode' => $kode,
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ], $attributes));
    }

    public function test_dua_tenant_boleh_pakai_kode_yang_sama(): void
    {
        $a = $this->makeVoucher($this->tenantA->id, 'PROMO10');
        $b = $this->makeVoucher($this->tenantB->id, 'PROMO10');

        $this->assertNotSame($a->id, $b->id);
        $this->assertSame(2, Voucher::query()->where('kode', 'PROMO10')->count());
    }

    public function test_voucher_tenant_tidak_terlihat_oleh_tenant_lain(): void
    {
        $this->makeVoucher($this->tenantA->id, 'RAHASIA-A');

        $this->assertNotNull($this->resolver->resolve($this->tenantA, 'RAHASIA-A'));
        $this->assertNull(
            $this->resolver->resolve($this->tenantB, 'RAHASIA-A'),
            'voucher tenant A tidak boleh berlaku di toko tenant B'
        );
    }

    public function test_voucher_platform_berlaku_di_semua_toko(): void
    {
        $this->makeVoucher(null, 'SEMUA-TOKO');

        $this->assertNotNull($this->resolver->resolve($this->tenantA, 'SEMUA-TOKO'));
        $this->assertNotNull($this->resolver->resolve($this->tenantB, 'SEMUA-TOKO'));
    }

    public function test_situs_platform_tidak_menemukan_voucher_tenant(): void
    {
        $this->makeVoucher($this->tenantA->id, 'MILIK-TENANT');

        $this->assertNull(
            $this->resolver->resolve(null, 'MILIK-TENANT'),
            'situs platform tidak boleh menemukan voucher milik tenant'
        );
    }

    public function test_voucher_tenant_menang_atas_voucher_platform_berkode_sama(): void
    {
        $this->makeVoucher(null, 'PROMO10', ['promo' => 20]);
        $milikTenant = $this->makeVoucher($this->tenantA->id, 'PROMO10', ['promo' => 3]);

        $hasil = $this->resolver->resolve($this->tenantA, 'PROMO10');

        $this->assertSame($milikTenant->id, $hasil->id);
        $this->assertSame(3.0, (float) $hasil->promo);

        // Tenant B tidak punya voucher sendiri -> dapat versi platform.
        $diB = $this->resolver->resolve($this->tenantB, 'PROMO10');
        $this->assertNull($diB->tenant_id);
        $this->assertSame(20.0, (float) $diB->promo);
    }

    public function test_kode_kosong_tidak_menemukan_apa_pun(): void
    {
        $this->makeVoucher($this->tenantA->id, 'ADA');

        $this->assertNull($this->resolver->resolve($this->tenantA, ''));
        $this->assertNull($this->resolver->resolve($this->tenantA, '   '));
        $this->assertNull($this->resolver->resolve($this->tenantA, null));
    }

    public function test_discount_for_menghormati_cap_dan_tanpa_cap(): void
    {
        // cap aktif
        $this->assertSame(5000, $this->makeVoucher(null, 'A', ['promo' => 10, 'max_potongan' => 5000])->discountFor(100000));

        // cap 0 = tanpa cap, bukan diskon nol
        $this->assertSame(10000, $this->makeVoucher(null, 'B', ['promo' => 10, 'max_potongan' => 0])->discountFor(100000));

        // diskon di bawah cap
        $this->assertSame(2000, $this->makeVoucher(null, 'C', ['promo' => 2, 'max_potongan' => 5000])->discountFor(100000));

        // promo 0
        $this->assertSame(0, $this->makeVoucher(null, 'D', ['promo' => 0])->discountFor(100000));

        // 90% dari 1.000 = 900 (bukan 1.000); sisanya tetap dibayar customer
        $this->assertSame(900, $this->makeVoucher(null, 'E', ['promo' => 90])->discountFor(1000));

        // promo 100% = diskon sebesar nominal, tidak pernah melebihi
        $this->assertSame(1000, $this->makeVoucher(null, 'F', ['promo' => 100])->discountFor(1000));
    }

    public function test_hapus_tenant_tidak_menghapus_voucher_platform(): void
    {
        $milikTenant = $this->makeVoucher($this->tenantA->id, 'IKUT-HAPUS');
        $milikPlatform = $this->makeVoucher(null, 'TETAP');

        // nullOnDelete: voucher tenant ikut kehilangan tenant_id, bukan hilang.
        $this->tenantA->delete();

        $this->assertNull($milikTenant->fresh()->tenant_id);
        $this->assertNotNull($milikPlatform->fresh());
    }
}
