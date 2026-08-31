<?php

namespace Tests\Unit;

use App\Models\Layanan;
use App\Services\ProductPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProductPricingServiceBulkProfitTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_profit_percentages_to_all_tiers_from_modal(): void
    {
        $product = Layanan::factory()->create([
            'harga' => 10000,
            'harga_member' => 10000,
            'harga_platinum' => 10000,
            'harga_gold' => 10000,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
        ]);

        app(ProductPricingService::class)->applyTierProfitPercentages($product, [
            'member' => 10,
            'platinum' => 20,
            'gold' => 30,
        ]);

        $this->assertSame(10000, (int) $product->harga);
        $this->assertSame(11000, (int) $product->harga_member);
        $this->assertSame(12000, (int) $product->harga_platinum);
        $this->assertSame(13000, (int) $product->harga_gold);
        $this->assertSame(10, (int) $product->profit_member);
        $this->assertSame(20, (int) $product->profit_platinum);
        $this->assertSame(30, (int) $product->profit_gold);
    }

    public function test_empty_tiers_keep_existing_values(): void
    {
        $product = Layanan::factory()->create([
            'harga' => 10000,
            'harga_member' => 11000,
            'harga_platinum' => 12000,
            'harga_gold' => 13000,
            'profit_member' => 10,
            'profit_platinum' => 20,
            'profit_gold' => 30,
        ]);

        app(ProductPricingService::class)->applyTierProfitPercentages($product, [
            'member' => 15,
            'platinum' => null,
            'gold' => null,
        ]);

        $this->assertSame(11500, (int) $product->harga_member);
        $this->assertSame(12000, (int) $product->harga_platinum);
        $this->assertSame(13000, (int) $product->harga_gold);
        $this->assertSame(15, (int) $product->profit_member);
        $this->assertSame(20, (int) $product->profit_platinum);
        $this->assertSame(30, (int) $product->profit_gold);
        $this->assertSame(10000, (int) $product->harga);
    }

    public function test_it_rejects_profit_outside_supported_range(): void
    {
        $product = Layanan::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        app(ProductPricingService::class)->applyTierProfitPercentages($product, [
            'member' => 101,
        ]);
    }
    public function test_it_rejects_profit_percentages_that_break_tier_order(): void
    {
        $product = Layanan::factory()->create([
            'harga' => 10000,
            'harga_member' => 10000,
            'harga_platinum' => 10000,
            'harga_gold' => 10000,
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ProductPricingService::class)->applyTierProfitPercentages($product, [
            'member' => 20,
            'platinum' => 10,
            'gold' => 30,
        ]);
    }
}
