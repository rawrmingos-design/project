<?php

namespace Tests\Unit;

use App\Models\Layanan;
use App\Services\ProductPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function rebase_keeps_existing_profit_percentages_instead_of_absolute_margin(): void
    {
        $product = Layanan::factory()->create([
            'harga' => 10000,
            'harga_member' => 10500,
            'harga_platinum' => 11000,
            'harga_gold' => 11500,
            'profit_member' => 5,
            'profit_platinum' => 10,
            'profit_gold' => 15,
        ]);

        app(ProductPricingService::class)->rebaseFromNewBaseCostKeepingMargins($product, 5000);

        $this->assertSame(5000, (int) $product->harga);
        $this->assertSame(5250, (int) $product->harga_member);
        $this->assertSame(5500, (int) $product->harga_platinum);
        $this->assertSame(5750, (int) $product->harga_gold);
        $this->assertSame(5, (int) $product->profit_member);
        $this->assertSame(10, (int) $product->profit_platinum);
        $this->assertSame(15, (int) $product->profit_gold);
    }

    /** @test */
    public function rebase_can_fallback_to_derived_percent_when_profit_columns_are_missing(): void
    {
        $product = Layanan::factory()->create([
            'harga' => 10000,
            'harga_member' => 10800,
            'harga_platinum' => 11000,
            'harga_gold' => 11200,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
        ]);

        $product->profit_member = null;
        $product->profit_platinum = null;
        $product->profit_gold = null;

        app(ProductPricingService::class)->rebaseFromNewBaseCostKeepingMargins($product, 5000);

        $this->assertSame(5400, (int) $product->harga_member);
        $this->assertSame(5500, (int) $product->harga_platinum);
        $this->assertSame(5600, (int) $product->harga_gold);
        $this->assertSame(8, (int) $product->profit_member);
        $this->assertSame(10, (int) $product->profit_platinum);
        $this->assertSame(12, (int) $product->profit_gold);
    }
}
