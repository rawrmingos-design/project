<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecentPurchasesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_purchases_endpoint_returns_masked_successful_purchases_only(): void
    {
        $kategori = Kategori::factory()->create([
            'thumbnail' => 'assets/thumbnail/mobile-legends.webp',
            'status' => 'active',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Mobile Legends 86 Diamond',
        ]);

        Pembelian::factory()->sukses()->create([
            'layanan' => $layanan->layanan,
            'username' => 'fahmimaulana',
            'active_layanan_id' => $layanan->id,
            'updated_at' => now(),
        ]);

        Pembelian::factory()->proses()->create([
            'layanan' => $layanan->layanan,
            'username' => 'prosesuser',
            'active_layanan_id' => $layanan->id,
        ]);

        $response = $this->getJson(route('recent-purchases.index'));

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.item', 'Mobile Legends 86 Diamond')
            ->assertJsonPath('0.image', '/assets/thumbnail/mobile-legends.webp');

        $buyerName = (string) $response->json('0.name');

        $this->assertNotSame('fahmimaulana', $buyerName);
        $this->assertStringContainsString('*', $buyerName);
    }

    public function test_recent_purchases_endpoint_decodes_html_entities_for_display_text(): void
    {
        $kategori = Kategori::factory()->create([
            'thumbnail' => 'assets/thumbnail/item.webp',
            'status' => 'active',
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => 'Sword & Shield',
        ]);

        Pembelian::factory()->sukses()->create([
            'layanan' => 'Sword &amp; Shield',
            'nickname' => 'R&amp;DPlayer',
            'username' => 'fallbackuser',
            'active_layanan_id' => $layanan->id,
            'updated_at' => now(),
        ]);

        $response = $this->getJson(route('recent-purchases.index'));

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.item', 'Sword & Shield');

        $this->assertStringContainsString('&', (string) $response->json('0.name'));
        $this->assertStringNotContainsString('&amp;', (string) $response->json('0.name'));
    }

    public function test_live_sales_toast_partial_uses_recent_purchases_endpoint(): void
    {
        $html = view('template.id.partials.live-sales-toast')->render();

        $this->assertStringContainsString('id="live-sales-toast"', $html);
        $this->assertStringContainsString(route('recent-purchases.index'), $html);
    }

    public function test_live_sales_toast_wrapper_hides_toast_when_setting_disabled(): void
    {
        $html = view('template.id.partials.live-sales-toast-wrapper', [
            'config' => (object) ['live_sales_enabled' => false, 'logo_favicon' => null],
        ])->render();

        $this->assertStringNotContainsString('id="live-sales-toast"', $html);
    }

    public function test_live_sales_toast_wrapper_defaults_to_enabled(): void
    {
        $html = view('template.id.partials.live-sales-toast-wrapper', [
            'config' => (object) ['logo_favicon' => null],
        ])->render();

        $this->assertStringContainsString('id="live-sales-toast"', $html);
    }
}
