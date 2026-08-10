<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Produks\Pages\CreateProduk;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

class ProdukCheckIdConfigTest extends AdminTestCase
{
    use RefreshDatabase;

    public function test_creating_produk_does_not_persist_removed_inquiry_fields(): void
    {
        \App\Models\Provider::create(['code' => 'digiflazz', 'name' => 'Digiflazz']);

        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $kategori = Kategori::factory()->create([
            'tipe' => 'game',
        ]);

        Livewire::test(CreateProduk::class)
            ->fillForm([
                'kategori_id' => $kategori->id,
                'layanan' => 'Produk Check ID Test',
                'provider_id' => 'CHECK_ID_ORDER_SKU',
                'provider' => 'digiflazz',
                'status' => 'available',
                'catatan' => 'test',
                'harga' => 10000,
                'profit_member' => 10,
                'profit_platinum' => 5,
                'profit_gold' => 7,
                'harga_member' => 11000,
                'harga_platinum' => 10500,
                'harga_gold' => 10700,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('layanans', [
            'provider_id' => 'CHECK_ID_ORDER_SKU',
        ]);
    }
}
