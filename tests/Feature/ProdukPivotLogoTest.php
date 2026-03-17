<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\Produks\Pages\CreateProduk;
use App\Models\Kategori;
use App\Models\MediaAsset;
use App\Models\Paket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class ProdukPivotLogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_produk_attaches_selected_paket(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $kategori = Kategori::factory()->create([
            'tipe' => 'pulsa',
        ]);

        $paket = Paket::query()->create([
            'nama' => 'Paket Test',
        ]);

        Livewire::test(CreateProduk::class)
            ->fillForm([
                'kategori_id' => $kategori->id,
                'layanan' => 'Produk Pivot Test',
                'provider_id' => 'PIVOT_TEST_1',
                'provider' => 'manual',
                'status' => 'available',
                'paket' => [$paket->id],
                'catatan' => 'test',
                'harga' => 10000,
                'profit_member' => 10,
                'profit_platinum' => 5,
                'profit_gold' => 7,
                'harga_member' => 11000,
                'harga_platinum' => 10500,
                'harga_gold' => 10700,
            ])
            ->call('create');

        $produkId = DB::table('layanans')->where('provider_id', 'PIVOT_TEST_1')->value('id');

        $this->assertNotNull($produkId);

        $this->assertDatabaseHas('paket_layanans', [
            'layanan_id' => $produkId,
            'paket_id' => $paket->id,
        ]);
    }

    public function test_creating_produk_with_media_library_logo_sets_pivot_logo_path(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $kategori = Kategori::factory()->create([
            'tipe' => 'pulsa',
        ]);

        $paket = Paket::query()->create([
            'nama' => 'Paket Test',
        ]);

        File::ensureDirectoryExists(public_path('assets/product_logo'));
        File::put(public_path('assets/product_logo/test-logo.png'), 'test');

        $asset = MediaAsset::query()->create([
            'name' => 'Test Logo',
            'folder' => 'produk',
            'alt_text' => 'Test Logo',
            'path' => '/assets/product_logo/test-logo.png',
        ]);

        Livewire::test(CreateProduk::class)
            ->fillForm([
                'kategori_id' => $kategori->id,
                'layanan' => 'Produk Pivot Logo Test',
                'provider_id' => 'PIVOT_TEST_2',
                'provider' => 'manual',
                'status' => 'available',
                'paket' => [$paket->id],
                'catatan' => 'test',
                'harga' => 10000,
                'profit_member' => 10,
                'profit_platinum' => 5,
                'profit_gold' => 7,
                'harga_member' => 11000,
                'harga_platinum' => 10500,
                'harga_gold' => 10700,
                'product_logo_input_mode' => 'library',
                'product_logo_media_asset_id' => $asset->id,
            ])
            ->call('create');

        $produkId = DB::table('layanans')->where('provider_id', 'PIVOT_TEST_2')->value('id');

        $this->assertNotNull($produkId);

        $this->assertDatabaseHas('paket_layanans', [
            'layanan_id' => $produkId,
            'paket_id' => $paket->id,
            'product_logo' => '/assets/product_logo/test-logo.png',
        ]);
    }
}
