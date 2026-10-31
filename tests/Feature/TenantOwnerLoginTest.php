<?php

use App\Models\Pembelian;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Pemilik toko (`tenant_owner`) harus bisa login lewat halaman login publik.
 *
 * Kenapa dijaga: R1 menambahkan role `tenant_owner` ke enum `users` supaya
 * pemilik toko tidak lagi diberi role `Gold`, tapi allowlist di
 * `LoginController::store()` tidak ikut diperbarui. Akibatnya password yang
 * benar tetap ditolak dengan pesan "Username / password mismatch" — pesan yang
 * menyesatkan, seolah kredensialnya salah. Pemilik toko jadi tidak punya pintu
 * masuk sama sekali karena `canAccessPanel()` hanya untuk `Admin`.
 *
 * Tes ini mengunci DUA hal: (1) tiap role yang sah bisa login, (2) tiap role
 * yang tidak sah tetap ditolak — supaya perbaikan "buka satu role" tidak
 * berubah jadi "buka semua role".
 */
function makeUserWithRole(string $role, ?int $tenantId = null, array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'role' => $role,
        'password' => Hash::make('RahasiaUji123!'),
        'tenant_id' => $tenantId,
    ], $overrides));
}

function makeTenantFor(User $owner, array $overrides = []): Tenant
{
    return Tenant::create(array_merge([
        'name' => 'Toko Uji Login',
        'subdomain' => 'tokouji' . uniqid(),
        'status' => Tenant::STATUS_ACTIVE,
        'owner_user_id' => $owner->id,
    ], $overrides));
}

test('tenant_owner bisa login dan diarahkan ke dashboard tokonya', function () {
    config(['app.url' => 'https://platform.test']);

    $owner = makeUserWithRole('tenant_owner');
    $tenant = makeTenantFor($owner);

    $response = $this->post('/id/sign-in', [
        'username' => $owner->username,
        'password' => 'RahasiaUji123!',
    ]);

    $this->assertAuthenticatedAs($owner);

    $expected = 'https://' . $tenant->subdomain . '.platform.test/dashboard';
    expect($response->headers->get('Location'))->toBe($expected);
});

test('tenant_owner dengan subdomain kosong tetap login, tidak di-logout', function () {
    config(['app.url' => 'https://platform.test']);

    $owner = makeUserWithRole('tenant_owner');
    makeTenantFor($owner, ['subdomain' => '']);

    $this->post('/id/sign-in', [
        'username' => $owner->username,
        'password' => 'RahasiaUji123!',
    ]);

    $this->assertAuthenticatedAs($owner);
});

test('tenant_owner tanpa tenant tetap login, tidak 500', function () {
    config(['app.url' => 'https://platform.test']);

    $owner = makeUserWithRole('tenant_owner');

    $this->post('/id/sign-in', [
        'username' => $owner->username,
        'password' => 'RahasiaUji123!',
    ]);

    $this->assertAuthenticatedAs($owner);
});

test('pembeli biasa tetap diarahkan ke dashboard platform, bukan subdomain', function () {
    config(['app.url' => 'https://platform.test']);

    $member = makeUserWithRole('Member');
    makeTenantFor($member);

    $response = $this->post('/id/sign-in', [
        'username' => $member->username,
        'password' => 'RahasiaUji123!',
    ]);

    $this->assertAuthenticatedAs($member);

    $location = (string) $response->headers->get('Location');
    expect($location)->not->toContain($member->username);
    expect($location)->toContain('/dashboard');
    expect(substr_count($location, '.'))->toBeLessThanOrEqual(2);
});

test('role Admin tetap ditolak di login publik dan diarahkan ke panel', function () {
    $admin = makeUserWithRole('Admin');

    $this->post('/id/sign-in', [
        'username' => $admin->username,
        'password' => 'RahasiaUji123!',
    ]);

    $this->assertGuest();
});

test('role di luar allowlist tetap ditolak', function () {
    foreach (['Reseller', 'Affiliate', 'Suspended', ''] as $role) {
        $user = makeUserWithRole($role === '' ? 'Member' : $role);

        if ($role === '') {
            // pastikan string kosong benar-benar tidak lolos allowlist
            $user->forceFill(['role' => ''])->save();
        }

        $this->post('/id/sign-in', [
            'username' => $user->username,
            'password' => 'RahasiaUji123!',
        ]);

        // assertGuest($guard) — argumen pertama adalah nama guard, bukan pesan.
        $this->assertGuest();
        expect(Auth::check())->toBeFalse("role '{$role}' seharusnya ditolak");
    }
});

test('password salah tetap ditolak untuk tenant_owner', function () {
    $owner = makeUserWithRole('tenant_owner');
    makeTenantFor($owner);

    $this->post('/id/sign-in', [
        'username' => $owner->username,
        'password' => 'PasswordSalah999',
    ]);

    $this->assertGuest();
});

/**
 * Otorisasi halaman tenant: hanya pemiliknya yang boleh masuk.
 * Kalau `owner_user_id` tidak dicocokkan, dashboard toko bisa dibaca siapa pun
 * yang login — termasuk pemilik toko lain.
 */
test('hanya owner tenant yang boleh membuka dashboard toko', function () {
    config(['app.url' => 'https://platform.test', 'tenancy.disabled' => false]);

    $owner = makeUserWithRole('tenant_owner');
    $tenant = makeTenantFor($owner);

    $outsider = makeUserWithRole('tenant_owner');
    makeTenantFor($outsider);

    $host = $tenant->subdomain . '.platform.test';

    $this->actingAs($owner)
        ->get('https://' . $host . '/dashboard')
        ->assertOk();

    $this->actingAs($outsider)
        ->get('https://' . $host . '/dashboard')
        ->assertForbidden();
});

/**
 * Angka dashboard harus ter-scope tenant. Tanpa global scope, omzet dan komisi
 * satu toko akan menghitung order toko lain — kebocoran data yang paling
 * berbahaya karena terlihat wajar (angkanya cuma "kebesaran").
 */
test('omzet dan komisi dashboard tidak menghitung order tenant lain', function () {
    config(['app.url' => 'https://platform.test', 'tenancy.disabled' => false]);

    $owner = makeUserWithRole('tenant_owner');
    $tenant = makeTenantFor($owner);

    $otherOwner = makeUserWithRole('tenant_owner');
    $otherTenant = makeTenantFor($otherOwner);

    Pembelian::factory()->create([
        'tenant_id' => $tenant->id,
        'harga' => 52000,
        'profit' => 1500,
    ]);

    Pembelian::factory()->create([
        'tenant_id' => $otherTenant->id,
        'harga' => 900000,
        'profit' => 99999,
    ]);

    $response = $this->actingAs($owner)
        ->get('https://' . $tenant->subdomain . '.platform.test/dashboard')
        ->assertOk();

    $html = $response->getContent();

    expect($html)->toContain('52.000');
    expect($html)->not->toContain('900.000');
    expect($html)->not->toContain('99.999');
});

test('tamu diarahkan ke login saat membuka dashboard toko', function () {
    config(['app.url' => 'https://platform.test', 'tenancy.disabled' => false]);

    $owner = makeUserWithRole('tenant_owner');
    $tenant = makeTenantFor($owner);

    $this->get('https://' . $tenant->subdomain . '.platform.test/dashboard')
        ->assertRedirect();
});
