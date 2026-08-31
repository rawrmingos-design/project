<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Services\BulkProductProfitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkProductProfitServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_scope_applies_pricing_and_stores_audit_snapshot(): void
    {
        $category = Kategori::factory()->create(['nama' => 'Mobile Legends']);
        $otherCategory = Kategori::factory()->create(['nama' => 'Free Fire']);
        $target = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'harga' => 10000,
            'harga_member' => 10000,
            'harga_platinum' => 10000,
            'harga_gold' => 10000,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
        ]);
        $outside = Layanan::factory()->create([
            'kategori_id' => $otherCategory->id,
            'harga' => 20000,
            'harga_member' => 20000,
            'harga_platinum' => 20000,
            'harga_gold' => 20000,
            'profit_member' => 0,
            'profit_platinum' => 0,
            'profit_gold' => 0,
        ]);

        $service = app(BulkProductProfitService::class);
        $query = $service->buildTargetQuery(['scope_type' => 'category', 'kategori_id' => $category->id]);
        $bulk = $service->apply($query, ['member' => 10, 'platinum' => 20, 'gold' => 30], null, [
            'scope_type' => 'category',
            'kategori_id' => $category->id,
        ]);

        $target->refresh();
        $outside->refresh();
        $this->assertSame(1, $bulk->matched_count);
        $this->assertSame(1, $bulk->updated_count);
        $this->assertSame(1, $bulk->items()->count());
        $this->assertSame(11000, $target->harga_member);
        $this->assertSame(12000, $target->harga_platinum);
        $this->assertSame(13000, $target->harga_gold);
        $this->assertSame(20000, $outside->harga_member);
    }

    public function test_selected_scope_does_not_include_unselected_products(): void
    {
        $first = Layanan::factory()->create();
        $second = Layanan::factory()->create();

        $query = app(BulkProductProfitService::class)->buildTargetQuery([
            'scope_type' => 'selected',
            'selected_ids' => [$first->id],
        ]);

        $this->assertSame([$first->id], $query->pluck('id')->all());
        $this->assertNotContains($second->id, $query->pluck('id')->all());
    }

    public function test_preview_returns_count_and_before_after_examples(): void
    {
        $product = Layanan::factory()->create([
            'harga' => 10000,
            'harga_member' => 10000,
            'harga_platinum' => 10000,
            'harga_gold' => 13000,
            'profit_member' => 10,
            'profit_platinum' => 20,
            'profit_gold' => 30,
        ]);

        $preview = app(BulkProductProfitService::class)->preview(
            Layanan::query()->whereKey($product->id),
            ['member' => 10],
        );

        $this->assertSame(1, $preview['matched_count']);
        $this->assertSame(10000, $preview['examples'][0]['before']['harga_member']);
        $this->assertSame(11000, $preview['examples'][0]['after']['harga_member']);
    }

    public function test_preview_rejects_empty_profit_update(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(BulkProductProfitService::class)->preview(Layanan::query(), [
            'member' => null,
            'platinum' => null,
            'gold' => null,
        ]);
    }
}
