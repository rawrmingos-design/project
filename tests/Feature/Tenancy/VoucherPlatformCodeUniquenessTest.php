<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keunikan kode voucher PLATFORM dijaga aplikasi, bukan indeks DB.
 *
 * MySQL tidak punya `NULLS NOT DISTINCT`, jadi unique `(tenant_id, kode)` tidak
 * menahan dua baris `tenant_id NULL, kode 'X'`. Karena itu validasinya ada di
 * controller/form — dan tes ini yang memastikan validasi itu tidak hilang saat
 * seseorang "merapikan" kode.
 *
 * Kalau suatu hari pindah ke PostgreSQL dan memakai `nullsNotDistinct()`, tes ini
 * tetap benar: kode platform dobel harus DITOLAK, dari mana pun penolakannya.
 */
class VoucherPlatformCodeUniquenessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Admin']);
    }

    public function test_kode_platform_dobel_ditolak(): void
    {
        Voucher::create([
            'tenant_id' => null,
            'kode' => 'PROMO10',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        // Rute panel legacy adalah `/voucher` (tunggal), bukan `/admin/vouchers`.
        $this->actingAs($this->admin())
            ->post('/voucher', [
                'kode' => 'PROMO10',
                'promo' => 2,
                'stock' => 5,
                'mintrx' => 0,
                'max_potongan' => 0,
            ])
            ->assertSessionHasErrors('kode');

        $this->assertSame(1, Voucher::query()->where('kode', 'PROMO10')->count());
    }

    public function test_kode_yang_dipakai_tenant_tidak_menghalangi_voucher_platform(): void
    {
        $owner = User::factory()->create(['role' => 'tenant_owner']);
        $tenant = Tenant::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Toko Uji',
            'subdomain' => 'toko-uji',
            'tier' => 'business',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        Voucher::create([
            'tenant_id' => $tenant->id,
            'kode' => 'PROMO10',
            'promo' => 5,
            'stock' => 10,
            'mintrx' => 0,
            'max_potongan' => 0,
        ]);

        // Platform tetap boleh memakai kode itu: cakupannya berbeda.
        $this->actingAs($this->admin())
            ->post('/voucher', [
                'kode' => 'PROMO10',
                'promo' => 2,
                'stock' => 5,
                'mintrx' => 0,
                'max_potongan' => 0,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, Voucher::query()->where('kode', 'PROMO10')->count());
    }
}
