<?php

namespace Database\Seeders;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class E2EBrowserSeeder extends Seeder
{
    public const POPUP_ID = 900001;

    public function run(): void
    {
        DB::table('setting_webs')->updateOrInsert(
            ['id' => 1],
            [
                'judul_web' => 'P06 Browser Test',
                'deskripsi_web' => 'Deterministic browser test storefront.',
                'keywords' => 'browser,test',
                'logo_header' => 'assets/logo/favicon.webp',
                'logo_footer' => 'assets/logo/favicon.webp',
                'logo_favicon' => 'assets/logo/favicon.webp',
                'url_wa' => 'https://wa.me/6200000000000',
                'url_ig' => 'https://instagram.com/example',
                'url_tiktok' => 'https://tiktok.com/@example',
                'url_youtube' => 'https://youtube.com/@example',
                'url_fb' => 'https://facebook.com/example',
                'topupindo_api' => '',
                'warna1' => '#111827',
                'warna2' => '#1f2937',
                'warna3' => '#f97316',
                'warna4' => '#fb923c',
                'paydisini_apikey' => '',
                'order_prefik' => 'E2E',
                'public_theme' => 'bangjeff',
                'home_popup_enabled' => true,
                'live_sales_enabled' => false,
                'google_analytics_id' => null,
                'facebook_pixel_id' => null,
                'google_tag_manager_id' => null,
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-01 00:00:00',
            ],
        );

        DB::table('beritas')->updateOrInsert(
            ['id' => self::POPUP_ID],
            [
                'path' => null,
                'tipe' => 'popup',
                'urutan' => 0,
                'deskripsi' => '<p>Pengumuman browser test P06.</p>',
                'created_at' => '2026-01-01 00:00:00',
                'updated_at' => '2026-01-01 00:00:00',
            ],
        );

        $this->seedUsers();
        $this->seedStorefront();
    }

    private function seedUsers(): void
    {
        User::query()->updateOrCreate(
            ['username' => 'e2e-member'],
            [
                'name' => 'E2E Member',
                'email' => 'e2e-member@example.test',
                'password' => Hash::make('e2e-password'),
                'role' => 'Member',
                'balance' => 100000,
                'point_balance' => 0,
                'no_wa' => '6281200000001',
                'affiliate_status' => 'inactive',
            ],
        );

        User::query()->updateOrCreate(
            ['username' => 'e2e-admin'],
            [
                'name' => 'E2E Admin',
                'email' => 'e2e-admin@example.test',
                'password' => Hash::make('e2e-password'),
                'role' => 'Admin',
                'balance' => 0,
                'point_balance' => 0,
                'no_wa' => '6281200000002',
                'affiliate_status' => 'inactive',
            ],
        );
    }

    private function seedStorefront(): void
    {
        $categoryType = CategoryType::query()->updateOrCreate(
            ['slug' => 'e2e-games'],
            ['name' => 'E2E Games', 'sort' => 1],
        );

        $category = Kategori::query()->updateOrCreate(
            ['kode' => 'e2e-game'],
            [
                'nama' => 'E2E Game',
                'sub_nama' => 'Deterministic test game',
                'status' => 'active',
                'thumbnail' => 'assets/logo/favicon.webp',
                'banner' => 'assets/logo/favicon.webp',
                'tipe' => 'game',
                'server_id' => false,
                'require_user_id' => true,
                'deskripsi_game' => '<p>Deterministic browser test category.</p>',
                'deskripsi_field' => 'Masukkan User ID untuk melanjutkan.',
                'category_type_id' => $categoryType->id,
            ],
        );

        Layanan::query()->updateOrCreate(
            ['kategori_id' => $category->id, 'provider_id' => 'e2e-product-1'],
            [
                'layanan' => 'E2E Product 10000',
                'provider' => 'manual',
                'harga' => 10000,
                'harga_member' => 10000,
                'harga_platinum' => 10000,
                'harga_gold' => 10000,
                'profit_member' => 0,
                'profit_platinum' => 0,
                'profit_gold' => 0,
                'catatan' => 'E2E product',
                'status' => 'available',
                'product_logo' => 'assets/logo/favicon.webp',
                'is_flash_sale' => false,
                'harga_flash_sale' => 0,
                'stock_flash_sale' => 0,
            ],
        );

        Method::query()->updateOrCreate(
            ['code' => 'E2E_QRIS'],
            [
                'name' => 'E2E QRIS',
                'images' => 'assets/logo/favicon.webp',
                'keterangan' => 'E2E payment method',
                'tipe' => 'qris',
                'payment' => 'Tripay',
                'fee_percent' => 0,
                'fix_fee' => 0,
                'min_pembelian' => 10000,
                'max_pembelian' => 1000000,
                'statuspayment' => true,
            ],
        );
    }
}
