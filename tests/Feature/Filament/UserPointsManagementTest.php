<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\RelationManagers\PointHistoriesRelationManager;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\PointHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

uses(AdminTestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Filament User Management — Penyesuaian Poin & Pembersihan Section
|--------------------------------------------------------------------------
|
| Cakupan:
| - Aksi "Ubah Poin" pada tabel user (tambah/kurang) + audit point_histories
| - Kolom saldo poin tampil di tabel user
| - Section "Informasi Game" sudah dihapus dari form user
|
*/

test('admin dapat menambah poin user dari tabel manajemen user', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create(['point_balance' => 100]);

    Livewire::test(ListUsers::class)
        ->assertTableColumnVisible('point_balance')
        ->assertTableActionExists('adjust_points')
        ->callTableAction('adjust_points', $user->id, data: [
            'amount' => 250,
            'description' => 'Bonus event',
        ])
        ->assertHasNoTableActionErrors();

    expect($user->refresh()->point_balance)->toBe(350);

    $history = PointHistory::where('user_id', $user->id)->get();

    expect($history)->toHaveCount(1);
    expect($history->first()->type)->toBe('earn');
    expect($history->first()->points)->toBe(250);
    expect($history->first()->description)->toBe('Penyesuaian poin admin: Bonus event');
    expect($history->first()->order_id)->toBeNull();
});

test('admin dapat mengurangi poin user dan tercatat sebagai redeem', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create(['point_balance' => 400]);

    Livewire::test(ListUsers::class)
        ->callTableAction('adjust_points', $user->id, data: [
            'amount' => -150,
        ])
        ->assertHasNoTableActionErrors();

    expect($user->refresh()->point_balance)->toBe(250);

    $history = PointHistory::where('user_id', $user->id)->first();

    expect($history)->not->toBeNull();
    expect($history->type)->toBe('redeem');
    expect($history->points)->toBe(150);
    expect($history->description)->toBe('Penyesuaian poin admin');
});

test('pengurangan poin melebihi saldo ditolak tanpa mengubah data', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create(['point_balance' => 50]);

    Livewire::test(ListUsers::class)
        ->callTableAction('adjust_points', $user->id, data: [
            'amount' => -500,
        ])
        ->assertNotified();

    expect($user->refresh()->point_balance)->toBe(50);
    expect(PointHistory::where('user_id', $user->id)->count())->toBe(0);
});

test('jumlah poin 0 ditolak oleh validasi form', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create(['point_balance' => 50]);

    Livewire::test(ListUsers::class)
        ->callTableAction('adjust_points', $user->id, data: [
            'amount' => 0,
        ])
        ->assertHasTableActionErrors(['amount']);

    expect($user->refresh()->point_balance)->toBe(50);
    expect(PointHistory::where('user_id', $user->id)->count())->toBe(0);
});

test('section informasi game dihapus dari form user', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertFormFieldDoesNotExist('idgame')
        ->assertFormFieldDoesNotExist('servergame')
        ->assertFormFieldDoesNotExist('idgame2')
        ->assertFormFieldExists('email')
        ->assertFormFieldExists('role');
});

test('halaman edit user tetap dapat dibuka setelah section game dihapus', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create();

    $this->get(UserResource::getUrl('edit', ['record' => $user]))->assertOk();
});

test('resource user mendaftarkan relation manager riwayat poin', function () {
    expect(UserResource::getRelations())->toContain(PointHistoriesRelationManager::class);
});

test('halaman edit user menampilkan tab riwayat poin', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
        ->assertSuccessful()
        ->assertSee('Riwayat Poin')
        ->assertFormFieldExists('email');
});

test('riwayat poin user tampil di halaman edit user', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create(['point_balance' => 150]);

    $earn = PointHistory::create([
        'user_id' => $user->id,
        'type' => 'earn',
        'points' => 200,
        'description' => 'Penyesuaian poin admin: Bonus event',
    ]);

    $redeem = PointHistory::create([
        'user_id' => $user->id,
        'type' => 'redeem',
        'points' => 50,
        'description' => 'Penyesuaian poin admin',
    ]);

    Livewire::test(PointHistoriesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$earn, $redeem]);
});

test('riwayat poin hanya menampilkan milik user yang sedang dibuka', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $mine = PointHistory::create([
        'user_id' => $user->id,
        'type' => 'earn',
        'points' => 10,
        'description' => 'Hanya milik user ini',
    ]);

    $others = PointHistory::create([
        'user_id' => $otherUser->id,
        'type' => 'earn',
        'points' => 99,
        'description' => 'Milik user lain',
    ]);

    Livewire::test(PointHistoriesRelationManager::class, [
        'ownerRecord' => $user,
        'pageClass' => EditUser::class,
    ])
        ->assertCanSeeTableRecords([$mine])
        ->assertCanNotSeeTableRecords([$others]);
});
