<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Produks\Pages\CreateProduk;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\AdminTestCase;

class ProdukCheckIdConfigTest extends AdminTestCase
{
    use RefreshDatabase;

    public function test_creating_produk_persists_check_id_inquiry_config(): void
    {
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
                'check_id_enabled' => true,
                'check_id_provider' => 'digiflazz',
                'check_id_provider_sku' => 'CHECK_ID_INQUIRY_SKU',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('layanans', [
            'provider_id' => 'CHECK_ID_ORDER_SKU',
            'check_id_enabled' => true,
            'check_id_provider' => 'digiflazz',
            'check_id_provider_sku' => 'CHECK_ID_INQUIRY_SKU',
        ]);
    }

    public function test_disabled_check_id_clears_provider_and_sku(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $kategori = Kategori::factory()->create([
            'tipe' => 'game',
        ]);

        Livewire::test(CreateProduk::class)
            ->fillForm([
                'kategori_id' => $kategori->id,
                'layanan' => 'Produk Check ID Disabled Test',
                'provider_id' => 'CHECK_ID_DISABLED_ORDER_SKU',
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
                'check_id_enabled' => false,
                'check_id_provider' => 'digiflazz',
                'check_id_provider_sku' => 'SHOULD_CLEAR',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $row = DB::table('layanans')
            ->where('provider_id', 'CHECK_ID_DISABLED_ORDER_SKU')
            ->first();

        $this->assertNotNull($row);
        $this->assertFalse((bool) $row->check_id_enabled);
        $this->assertNull($row->check_id_provider);
        $this->assertNull($row->check_id_provider_sku);
    }
}
