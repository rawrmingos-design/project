<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\Withdrawal;
use App\Tenancy\TenantWithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Keputusan Admin atas pencairan: Setujui / Tolak.
 *
 * Uang hanya berpindah di satu tempat, dan hanya sekali:
 *  - Tolak  -> saldo dikembalikan TEPAT sekali, walau dipanggil berulang.
 *  - Setujui-> saldo TIDAK berubah (sudah dipotong saat pengajuan).
 *
 * Tanpa penjagaan ini, dua klik "Tolak" (atau dua admin bersamaan) bisa
 * mengembalikan saldo dua kali = mencetak uang dari udara.
 */
beforeEach(function () {
    config(['tenancy.disabled' => false, 'app.url' => 'https://topupengine.test']);

    $this->owner = User::factory()->create(['role' => 'tenant_owner', 'balance' => 0]);

    $this->tenant = Tenant::query()->create([
        'owner_user_id' => $this->owner->id,
        'name' => 'Toko Tolak',
        'subdomain' => 'toko-tolak',
        'tier' => 'starter',
        'status' => Tenant::STATUS_ACTIVE,
        'margin_config' => ['markup_type' => 'percent', 'markup_value' => 10],
    ]);
});

function buatPengajuan(int $userId, float $amount, string $status = 'pending'): Withdrawal
{
    return Withdrawal::create([
        'user_id' => $userId,
        'rekening' => 'BCA - 1234567890 - Budi',
        'total_transfer' => $amount,
        'biaya_admin' => 0,
        'status' => $status,
    ]);
}

test('tolak mengembalikan saldo tepat sekali', function () {
    // Saldo 30000 = sisa setelah 20000 diajukan dari 50000.
    $this->owner->update(['balance' => 30000]);
    $withdrawal = buatPengajuan($this->owner->id, 20000);

    expect(app(TenantWithdrawalService::class)->reject($withdrawal))->toBeTrue();
    expect((int) $this->owner->fresh()->balance)->toBe(50000);
    expect($withdrawal->fresh()->status)->toBe('rejected');
});

test('tolak dua kali tidak menggandakan pengembalian saldo', function () {
    $this->owner->update(['balance' => 30000]);
    $withdrawal = buatPengajuan($this->owner->id, 20000);
    $service = app(TenantWithdrawalService::class);

    expect($service->reject($withdrawal))->toBeTrue();
    expect($service->reject($withdrawal->fresh()))->toBeFalse();

    // Bukan 70000. Uang tidak boleh lahir dari klik kedua.
    expect((int) $this->owner->fresh()->balance)->toBe(50000);
});

test('pengajuan yang sudah disetujui tidak bisa ditolak', function () {
    $this->owner->update(['balance' => 30000]);
    $withdrawal = buatPengajuan($this->owner->id, 20000, 'success');

    expect(app(TenantWithdrawalService::class)->reject($withdrawal))->toBeFalse();
    expect((int) $this->owner->fresh()->balance)->toBe(30000);
    expect($withdrawal->fresh()->status)->toBe('success');
});

test('setujui tidak mengubah saldo karena sudah dipotong saat pengajuan', function () {
    $this->owner->update(['balance' => 30000]);
    $withdrawal = buatPengajuan($this->owner->id, 20000);

    expect(app(TenantWithdrawalService::class)->approve($withdrawal, 'bukti_withdraw/bukti.jpg'))->toBeTrue();
    expect((int) $this->owner->fresh()->balance)->toBe(30000);
    expect($withdrawal->fresh()->status)->toBe('success');
    expect($withdrawal->fresh()->bukti_transfer)->toBe('bukti_withdraw/bukti.jpg');
});

test('setujui dua kali tidak berubah jadi dua status', function () {
    $this->owner->update(['balance' => 30000]);
    $withdrawal = buatPengajuan($this->owner->id, 20000);
    $service = app(TenantWithdrawalService::class);

    expect($service->approve($withdrawal, 'a.jpg'))->toBeTrue();
    expect($service->approve($withdrawal->fresh(), 'b.jpg'))->toBeFalse();

    expect($withdrawal->fresh()->bukti_transfer)->toBe('a.jpg');
    expect((int) $this->owner->fresh()->balance)->toBe(30000);
});
