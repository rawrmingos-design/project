<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Provider;
use Illuminate\Database\Seeder;

/**
 * Layanan uji dengan provider internal `manual` — order otomatis SUKSES
 * tanpa panggilan API provider apa pun.
 *
 * Gunanya untuk menguji alur order sukses end-to-end (bot Telegram/WhatsApp
 * maupun storefront) tanpa kredensial provider, kuota, atau menunggu
 * callback. `case 'manual'` di OrderProcessingService mengembalikan
 * success=true + status Sukses seketika.
 *
 * SIFAT:
 *  - IDEMPOTEN: memakai updateOrCreate, aman dijalankan berulang kali.
 *  - TIDAK memakai truncate, jadi tidak menghapus layanan katalog nyata.
 *  - Sengaja TIDAK dipanggil dari DatabaseSeeder supaya produk uji ini
 *    tidak pernah masuk ke database produksi. Jalankan manual:
 *
 *      php artisan db:seed --class=ManualProviderTestProductsSeeder
 *
 * PENTING: produk ini berstatus `available`, dan katalog TIDAK memfilter
 * provider — jadi begitu di-seed di produksi, produk ini akan tampil di
 * storefront publik. Jangan jalankan di produksi.
 */
class ManualProviderTestProductsSeeder extends Seeder
{
    /**
     * @var array<int, array{kategori: string, sku: string, nama: string, harga: int, catatan: string}>
     */
    private array $products = [
        [
            'kategori' => 'mobile-legends',
            'sku' => 'ML_MANUAL_1',
            'nama' => '1 Diamonds (Test Manual)',
            'harga' => 1000,
            'catatan' => 'Test product for manual provider E2E flow',
        ],
        [
            'kategori' => 'free-fire',
            'sku' => 'FF_MANUAL_1',
            'nama' => '5 Diamond (Test Manual)',
            'harga' => 1000,
            'catatan' => 'Test product for manual provider E2E flow',
        ],
    ];

    public function run(): void
    {
        Provider::query()->updateOrCreate(
            ['code' => 'manual'],
            ['name' => 'MANUAL', 'type' => 'manual', 'is_active' => 1, 'status' => 1],
        );

        foreach ($this->products as $spec) {
            $kategori = Kategori::query()->where('kode', $spec['kategori'])->first();

            if (! $kategori) {
                $this->command?->warn("Kategori '{$spec['kategori']}' tidak ditemukan — produk {$spec['sku']} dilewati.");

                continue;
            }

            $layanan = Layanan::query()->updateOrCreate(
                ['kategori_id' => $kategori->id, 'provider_id' => $spec['sku']],
                [
                    'layanan' => $spec['nama'],
                    'provider' => 'manual',
                    'harga' => $spec['harga'],
                    // Harga per peran dinaikkan tipis di atas harga dasar, sama
                    // seperti pola produk uji ML yang sudah terbukti jalan.
                    'harga_member' => $spec['harga'] + 50,
                    'harga_platinum' => $spec['harga'] + 40,
                    'harga_gold' => $spec['harga'] + 30,
                    'profit_member' => 5,
                    'profit_platinum' => 4,
                    'profit_gold' => 3,
                    'status' => 'available',
                    'catatan' => $spec['catatan'],
                ],
            );

            $this->command?->info(sprintf(
                'Layanan manual siap: id=%d | %s | %s | sku=%s | Rp %s | kategori=%s',
                $layanan->id,
                $layanan->layanan,
                $layanan->provider,
                $layanan->provider_id,
                number_format((int) $layanan->harga_member, 0, ',', '.'),
                $kategori->nama,
            ));
        }
    }
}
