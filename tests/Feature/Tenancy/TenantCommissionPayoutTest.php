<?php

use App\Models\Pembelian;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\PembelianStatus;
use App\Tenancy\TenantCommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Pencairan komisi tenant — MANUAL.
 *
 * Aturan yang dijaga di sini:
 *  1. Komisi hanya jadi saldo kalau order benar-benar `success` (bukan
 *     `processing`), dan hanya sekali (idempoten).
 *  2. Angka "komisi" di dashboard = uang yang SUDAH dikredit, bukan
 *     SUM(profit) semua order. Kalau tidak, dashboard menjanjikan uang yang
 *     belum tentu jadi.
 *  3. Pengajuan pencairan memotong saldo TEPAT sekali, menolak nominal di atas
 *     saldo, menolak saldo nol, dan hanya boleh oleh owner tenant itu sendiri.
 */
beforeEach(function () {
    config([
        'tenancy.disabled' => false,
        'app.url' => 'https://topupengine.test',
    ]);

    $this->seed(\Database\Seeders\TierPricingSeeder::class);
    app(\App\Tenancy\TierPricingResolver::class)->flush();

    $this->owner = User::factory()->create(['role' => 'tenant_owner', 'balance' => 0]);

    $this->tenant = Tenant::query()->create([
        'owner_user_id' => $this->owner->id,
        'name' => 'Toko Komisi',
        'subdomain' => 'toko-komisi',
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
        'margin_config' => ['markup_type' => 'percent', 'markup_value' => 10],
    ]);

    $this->tenantB = Tenant::query()->create([
        'owner_user_id' => User::factory()->create(['role' => 'tenant_owner', 'balance' => 0])->id,
        'name' => 'Toko Lain',
        'subdomain' => 'toko-lain',
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
        'margin_config' => ['markup_type' => 'percent', 'markup_value' => 10],
    ]);
});

function buatOrder(?int $tenantId, int $profit, string $status): Pembelian
{
    $buyer = User::factory()->create([
        'tenant_id' => $tenantId,
        'role' => 'Member',
    ]);

    return Pembelian::withoutGlobalScope('tenant')->create([
        'tenant_id' => $tenantId,
        'order_id' => 'TEST-' . uniqid(),
        'layanan' => 'Produk Uji',
        'harga' => 10000,
        'profit' => $profit,
        'status' => $status,
        'user_id' => $buyer->id,
    ]);
}

// ---------------------------------------------------------------- komisi ---

test('order processing TIDAK mengkredit saldo owner', function () {
    $order = buatOrder($this->tenant->id, 1935, PembelianStatus::PROCESSING);

    expect(app(TenantCommissionService::class)->creditIfEligible($order))->toBeFalse();
    expect((int) $this->owner->fresh()->balance)->toBe(0);
});

test('order success mengkredit saldo owner sebesar profit', function () {
    $order = buatOrder($this->tenant->id, 1935, PembelianStatus::SUCCESS);

    expect(app(TenantCommissionService::class)->creditIfEligible($order))->toBeTrue();
    expect((int) $this->owner->fresh()->balance)->toBe(1935);
    expect($order->fresh()->tenant_commission_credited_at)->not->toBeNull();
});

test('kredit komisi idempoten: dipanggil dua kali tetap sekali', function () {
    $order = buatOrder($this->tenant->id, 1935, PembelianStatus::SUCCESS);
    $service = app(TenantCommissionService::class);

    expect($service->creditIfEligible($order))->toBeTrue();
    expect($service->creditIfEligible($order->fresh()))->toBeFalse();

    expect((int) $this->owner->fresh()->balance)->toBe(1935);
});

test('komisi tidak dikredit kalau profit nol atau negatif', function () {
    $service = app(TenantCommissionService::class);

    expect($service->creditIfEligible(buatOrder($this->tenant->id, 0, PembelianStatus::SUCCESS)))->toBeFalse();
    expect($service->creditIfEligible(buatOrder($this->tenant->id, -500, PembelianStatus::SUCCESS)))->toBeFalse();
    expect((int) $this->owner->fresh()->balance)->toBe(0);
});

test('order platform (tenant_id null) tidak mengkredit siapa pun', function () {
    $order = buatOrder(null, 5000, PembelianStatus::SUCCESS);

    expect(app(TenantCommissionService::class)->creditIfEligible($order))->toBeFalse();
    expect((int) $this->owner->fresh()->balance)->toBe(0);
});

// -------------------------------------------------------------- dashboard ---

test('dashboard hanya menghitung komisi yang SUDAH dikredit', function () {
    // Sudah dikredit.
    $credited = buatOrder($this->tenant->id, 1935, PembelianStatus::SUCCESS);
    app(TenantCommissionService::class)->creditIfEligible($credited);

    // Belum dikredit: processing dan gagal. Tidak boleh ikut dihitung.
    buatOrder($this->tenant->id, 7000, PembelianStatus::PROCESSING);
    buatOrder($this->tenant->id, 3000, PembelianStatus::FAILED);

    // Tenant lain, tidak boleh bocor.
    buatOrder($this->tenantB->id, 9999, PembelianStatus::SUCCESS);

    $response = $this->actingAs($this->owner)
        ->get("https://{$this->tenant->subdomain}.topupengine.test/dashboard");

    $response->assertStatus(200);

    // 1935 saja — bukan 1935+7000+3000, dan bukan 9999 milik tenant lain.
    expect($response->viewData('totalCommission'))->toBe(1935);

    // Order `processing` dilaporkan terpisah sebagai "menunggu", bukan sebagai
    // komisi yang sudah jadi. Order `failed` tidak masuk hitungan mana pun.
    expect($response->viewData('pendingCommission'))->toBe(7000);
});

// --------------------------------------------------------------- pencairan ---

test('owner bisa ajukan pencairan dan saldo terpotong tepat sekali', function () {
    $this->owner->update(['balance' => 50000]);

    $this->actingAs($this->owner)
        ->post("https://{$this->tenant->subdomain}.topupengine.test/withdrawals", [
            'amount' => 20000,
            'bank_destination' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ])
        ->assertRedirect();

    expect((int) $this->owner->fresh()->balance)->toBe(30000);

    $withdrawal = Withdrawal::where('user_id', $this->owner->id)->first();
    expect($withdrawal)->not->toBeNull();
    expect((float) $withdrawal->total_transfer)->toBe(20000.0);
    expect($withdrawal->status)->toBe('pending');
    expect($withdrawal->rekening)->toContain('BCA');
    expect($withdrawal->rekening)->toContain('1234567890');
});

test('pencairan di atas saldo ditolak dan saldo tidak berubah', function () {
    $this->owner->update(['balance' => 10000]);

    $this->actingAs($this->owner)
        ->post("https://{$this->tenant->subdomain}.topupengine.test/withdrawals", [
            'amount' => 20000,
            'bank_destination' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ])
        ->assertSessionHasErrors('amount');

    expect((int) $this->owner->fresh()->balance)->toBe(10000);
    expect(Withdrawal::where('user_id', $this->owner->id)->count())->toBe(0);
});

test('pencairan dengan saldo nol ditolak', function () {
    $this->owner->update(['balance' => 0]);

    $this->actingAs($this->owner)
        ->post("https://{$this->tenant->subdomain}.topupengine.test/withdrawals", [
            'amount' => 1000,
            'bank_destination' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ])
        ->assertSessionHasErrors('amount');

    expect(Withdrawal::where('user_id', $this->owner->id)->count())->toBe(0);
});

test('nominal nol atau negatif ditolak validasi', function () {
    $this->owner->update(['balance' => 50000]);

    foreach ([0, -5000] as $amount) {
        $this->actingAs($this->owner)
            ->post("https://{$this->tenant->subdomain}.topupengine.test/withdrawals", [
                'amount' => $amount,
                'bank_destination' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'Budi Santoso',
            ])
            ->assertSessionHasErrors('amount');
    }

    expect((int) $this->owner->fresh()->balance)->toBe(50000);
});

test('owner tenant lain tidak bisa mengajukan pencairan di tenant ini', function () {
    $this->tenantB->owner->update(['balance' => 50000]);

    $this->actingAs($this->tenantB->owner)
        ->post("https://{$this->tenant->subdomain}.topupengine.test/withdrawals", [
            'amount' => 10000,
            'bank_destination' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ])
        ->assertStatus(403);

    expect((int) $this->tenantB->owner->fresh()->balance)->toBe(50000);
    expect(Withdrawal::count())->toBe(0);
});

test('pembeli biasa tidak bisa mengajukan pencairan', function () {
    $customer = User::factory()->create([
        'tenant_id' => $this->tenant->id,
        'role' => 'Member',
        'balance' => 50000,
    ]);

    $this->actingAs($customer)
        ->post("https://{$this->tenant->subdomain}.topupengine.test/withdrawals", [
            'amount' => 10000,
            'bank_destination' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Budi Santoso',
        ])
        ->assertStatus(403);

    expect(Withdrawal::count())->toBe(0);
});

test('tamu diarahkan ke login, bukan membuat pencairan', function () {
    $this->post("https://{$this->tenant->subdomain}.topupengine.test/withdrawals", [
        'amount' => 10000,
        'bank_destination' => 'BCA',
        'account_number' => '1234567890',
        'account_name' => 'Budi Santoso',
    ])->assertRedirect();

    expect(Withdrawal::count())->toBe(0);
});

test('halaman pencairan menampilkan saldo dan riwayat milik sendiri', function () {
    $this->owner->update(['balance' => 25000]);

    Withdrawal::create([
        'user_id' => $this->owner->id,
        'rekening' => 'BCA - 1234567890 - Budi Santoso',
        'total_transfer' => 15000,
        'biaya_admin' => 0,
        'status' => 'pending',
    ]);

    Withdrawal::create([
        'user_id' => $this->tenantB->owner->id,
        'rekening' => 'BNI - 999 - Orang Lain',
        'total_transfer' => 77000,
        'biaya_admin' => 0,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->owner)
        ->get("https://{$this->tenant->subdomain}.topupengine.test/withdrawals");

    $response->assertStatus(200);
    expect($response->viewData('balance'))->toBe(25000);

    $riwayat = $response->viewData('withdrawals');
    expect($riwayat)->toHaveCount(1);
    expect((float) $riwayat->first()->total_transfer)->toBe(15000.0);
});
