<?php

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

uses(AdminTestCase::class, RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Filament User Management — Action "Ubah Saldo"
|--------------------------------------------------------------------------
|
| Notifikasi harus menampilkan saldo hasil akhir (setelah clamp ke 0),
| bukan angka mentah yang bisa minus.
|
*/

test('pengurangan saldo melebihi saldo menampilkan saldo aktual 0 di notifikasi', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create(['balance' => 3000]);

    Livewire::test(ListUsers::class)
        ->callTableAction('adjust_balance', $user->id, data: ['amount' => -5000])
        ->assertHasNoTableActionErrors();

    expect($user->refresh()->balance)->toBe(0);

    $notification = Livewire::test(Notifications::class)->instance()->notifications->first();

    expect($notification->getTitle())->toBe('Saldo berhasil diubah');
    expect((string) $notification->getBody())->toBe('Saldo baru: Rp 0');
});

test('penambahan saldo menampilkan saldo baru yang benar', function () {
    $this->actingAs(User::factory()->create(['role' => 'Admin']));

    $user = User::factory()->create(['balance' => 1000]);

    Livewire::test(ListUsers::class)
        ->callTableAction('adjust_balance', $user->id, data: ['amount' => 2500])
        ->assertHasNoTableActionErrors();

    expect($user->refresh()->balance)->toBe(3500);

    $notification = Livewire::test(Notifications::class)->instance()->notifications->first();

    expect((string) $notification->getBody())->toBe('Saldo baru: Rp 3.500');
});
