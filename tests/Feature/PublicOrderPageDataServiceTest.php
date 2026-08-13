<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Paket;
use App\Services\PublicOrderPageDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicOrderPageDataServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_projection_matches_account_validation_matrix(): void
    {
        $service = app(PublicOrderPageDataService::class);

        foreach (['game', 'populer', 'voucher', 'joki', 'jokigendong', 'vilogml'] as $type) {
            $category = Kategori::factory()->create([
                'kode' => $type . '-category',
                'tipe' => $type,
                'require_user_id' => $type === 'voucher',
            ]);

            $data = $service->getData($category);

            $this->assertSame($type === 'voucher', $data['category']['requireUserId']);
            $this->assertSame(in_array($type, ['game', 'populer'], true), $data['category']['requiresGameValidation']);
            $this->assertSame(in_array($type, ['joki', 'jokigendong', 'vilogml'], true) ? 'complex' : 'standard', $data['category']['orderMode']);
        }
    }

    public function test_unsupported_category_is_not_supported_for_inertia(): void
    {
        $category = Kategori::factory()->create(['tipe' => 'giftskin']);

        $this->assertFalse(app(PublicOrderPageDataService::class)->isSupportedForInertia($category));
    }

    public function test_load_packages_keeps_shape_and_uses_single_catalog_query(): void
    {
        $category = Kategori::factory()->create(['kode' => 'mobile-legends']);
        $otherCategory = Kategori::factory()->create(['kode' => 'free-fire']);

        $basicPackage = Paket::query()->create(['nama' => 'Basic Package']);
        $premiumPackage = Paket::query()->create(['nama' => 'Premium Package']);
        $emptyPackage = Paket::query()->create(['nama' => 'Empty Package']);

        $slowItem = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Slow Diamond Pack',
            'provider_id' => 'slow-pack',
            'harga_member' => 15000,
            'harga_platinum' => 13000,
            'harga_gold' => 14000,
            'is_flash_sale' => true,
            'harga_flash_sale' => 12000,
            'expired_flash_sale' => now()->addHour(),
        ]);

        $fastItem = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Fast Diamond Pack',
            'provider_id' => 'fast-pack',
            'harga_member' => 10000,
            'harga_platinum' => 9000,
            'harga_gold' => 9500,
        ]);

        $premiumItem = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Premium Diamond Pack',
            'provider_id' => 'premium-pack',
            'harga_member' => 25000,
            'harga_platinum' => 0,
            'harga_gold' => 23000,
        ]);

        $otherCategoryItem = Layanan::factory()->create([
            'kategori_id' => $otherCategory->id,
            'layanan' => 'Other Game Pack',
            'provider_id' => 'other-pack',
            'harga_platinum' => 5000,
        ]);

        DB::table('paket_layanans')->insert([
            [
                'paket_id' => $basicPackage->id,
                'layanan_id' => $slowItem->id,
                'product_logo' => 'assets/product_logo/slow.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'paket_id' => $basicPackage->id,
                'layanan_id' => $fastItem->id,
                'product_logo' => '/assets/product_logo/fast.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'paket_id' => $premiumPackage->id,
                'layanan_id' => $premiumItem->id,
                'product_logo' => 'assets/product_logo/premium.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'paket_id' => $emptyPackage->id,
                'layanan_id' => $otherCategoryItem->id,
                'product_logo' => 'assets/product_logo/other.png',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $packages = $this->invokeLoadPackages($category->id, 'Platinum');
        $queryLog = DB::getQueryLog();

        DB::disableQueryLog();

        $this->assertCount(1, $queryLog);
        $this->assertSame([
            [
                'name' => 'Basic Package',
                'items' => [
                    [
                        'id' => $fastItem->id,
                        'name' => 'Fast Diamond Pack',
                        'price' => 9000,
                        'productLogo' => '/assets/product_logo/fast.png',
                        'isFlashSale' => false,
                        'flashPrice' => 0,
                        'flashExpiresAt' => null,
                    ],
                    [
                        'id' => $slowItem->id,
                        'name' => 'Slow Diamond Pack',
                        'price' => 13000,
                        'productLogo' => '/assets/product_logo/slow.png',
                        'isFlashSale' => true,
                        'flashPrice' => 12000,
                        'flashExpiresAt' => $slowItem->expired_flash_sale->toIso8601String(),
                    ],
                ],
            ],
        ], $packages);
    }

    private function invokeLoadPackages(int $categoryId, string $role): array
    {
        $service = app(PublicOrderPageDataService::class);
        $method = new \ReflectionMethod($service, 'loadPackages');
        $method->setAccessible(true);

        return $method->invoke($service, $categoryId, $role);
    }
}
