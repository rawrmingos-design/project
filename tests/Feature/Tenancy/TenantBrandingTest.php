<?php

namespace Tests\Feature\Tenancy;

use App\Models\SettingWeb;
use App\Models\TierPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Teks merek di halaman tenant harus berasal dari setelan, bukan kode.
 *
 * Platform ini akan dijual, jadi nama "TopupEngine" yang dulu ditulis keras di
 * blade berarti setiap pembeli harus mengedit kode dan deploy untuk mengganti
 * merek. Tes ini menjaga agar nama itu hilang dari halaman dan digantikan
 * `setting_webs.judul_web`.
 */
class TenantBrandingTest extends TestCase
{
    use RefreshDatabase;

    private function makeSettings(array $values = []): SettingWeb
    {
        $columns = Schema::getColumns('setting_webs');
        $row = [];

        foreach ($columns as $column) {
            $name = $column['name'];

            if (in_array($name, ['id', 'created_at', 'updated_at'], true) || array_key_exists($name, $values)) {
                continue;
            }

            if ($column['nullable'] || $column['default'] !== null) {
                continue;
            }

            $row[$name] = match (true) {
                str_contains($column['type_name'], 'int') => 0,
                str_contains($column['type_name'], 'bool') => false,
                str_contains($column['type_name'], 'float'), str_contains($column['type_name'], 'double'), str_contains($column['type_name'], 'decimal') => 0,
                default => '',
            };
        }

        $id = DB::table('setting_webs')->insertGetId(array_merge($row, $values, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return SettingWeb::query()->findOrFail($id);
    }

    public function test_nama_platform_di_halaman_daftar_ikut_setelan(): void
    {
        config(['tenancy.disabled' => false]);
        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        $this->makeSettings(['judul_web' => 'Egymarket Digital']);

        $response = $this->get('/id/reseller-topup');

        $response->assertOk()
            ->assertSee('Egymarket Digital')
            // Merek keras lama tidak boleh muncul lagi.
            ->assertDontSee('TopupEngine');
    }

    public function test_nama_platform_cadangan_ke_app_name_kalau_setelan_kosong(): void
    {
        config([
            'tenancy.disabled' => false,
            'app.name' => 'Toko Contoh',
        ]);
        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        $this->makeSettings(['judul_web' => '']);

        $this->get('/id/reseller-topup')
            ->assertOk()
            ->assertSee('Toko Contoh')
            ->assertDontSee('TopupEngine');
    }

    public function test_harga_paket_masih_dirender_dari_db_setelah_branding_dipindah(): void
    {
        config(['tenancy.disabled' => false]);
        $this->seed(\Database\Seeders\TierPricingSeeder::class);
        $this->makeSettings(['judul_web' => 'Toko Uji']);

        $response = $this->get('/id/reseller-topup');

        $response->assertOk();

        // Harga kedua durasi tetap muncul, dibaca dari tier_pricing.
        foreach (['330.000', '800.000', '435.000', '898.000', '537.000', '989.000'] as $amount) {
            $response->assertSee($amount);
        }
    }
}
