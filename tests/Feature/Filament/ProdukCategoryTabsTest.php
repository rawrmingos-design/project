<?php

namespace Tests\Feature\Filament;

use App\Filament\Admin\Resources\Produks\Pages\ListProduks;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\AdminTestCase;

class ProdukCategoryTabsTest extends AdminTestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_layanan_by_game_category_tabs(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $mobileLegends = Kategori::factory()->create([
            'nama' => 'Mobile Legends',
            'tipe' => 'game',
            'status' => 'active',
        ]);
        $freeFire = Kategori::factory()->create([
            'nama' => 'Free Fire',
            'tipe' => 'game',
            'status' => 'active',
        ]);
        $inactiveGame = Kategori::factory()->create([
            'nama' => 'Inactive Game',
            'tipe' => 'game',
            'status' => 'inactive',
        ]);
        $nonGame = Kategori::factory()->create([
            'nama' => 'Pulsa Nasional',
            'tipe' => 'pulsa',
            'status' => 'active',
        ]);
        $emptyGame = Kategori::factory()->create([
            'nama' => 'Empty Game',
            'tipe' => 'game',
            'status' => 'active',
        ]);

        $mobileLegendsProduct = Layanan::factory()->create([
            'kategori_id' => (string) $mobileLegends->id,
            'layanan' => 'MLBB 100 Diamonds',
            'provider_id' => 'MLBB_100',
        ]);
        $freeFireProduct = Layanan::factory()->create([
            'kategori_id' => (string) $freeFire->id,
            'layanan' => 'Free Fire 70 Diamonds',
            'provider_id' => 'FF_70',
        ]);
        $inactiveProduct = Layanan::factory()->create([
            'kategori_id' => (string) $inactiveGame->id,
            'layanan' => 'Inactive Game Product',
            'provider_id' => 'INACTIVE_1',
        ]);
        $nonGameProduct = Layanan::factory()->create([
            'kategori_id' => (string) $nonGame->id,
            'layanan' => 'Pulsa 10K',
            'provider_id' => 'PULSA_10K',
        ]);

        $component = Livewire::test(ListProduks::class)
            ->assertSee('Semua')
            ->assertSee('Mobile Legends')
            ->assertSee('Free Fire');

        $tabs = $component->instance()->getTabs();

        $this->assertArrayHasKey('all', $tabs);
        $this->assertArrayHasKey("kategori-{$mobileLegends->id}", $tabs);
        $this->assertArrayHasKey("kategori-{$freeFire->id}", $tabs);
        $this->assertArrayNotHasKey("kategori-{$inactiveGame->id}", $tabs);
        $this->assertArrayNotHasKey("kategori-{$nonGame->id}", $tabs);
        $this->assertArrayNotHasKey("kategori-{$emptyGame->id}", $tabs);

        $component
            ->assertCanSeeTableRecords([
                $mobileLegendsProduct,
                $freeFireProduct,
                $inactiveProduct,
                $nonGameProduct,
            ])
            ->set('activeTab', "kategori-{$mobileLegends->id}")
            ->assertCanSeeTableRecords([$mobileLegendsProduct])
            ->assertCanNotSeeTableRecords([
                $freeFireProduct,
                $inactiveProduct,
                $nonGameProduct,
            ]);
    }
}
