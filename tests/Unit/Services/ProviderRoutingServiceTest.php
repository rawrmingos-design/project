<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Layanan;
use App\Models\ProviderPath;
use App\Models\SettingWeb;
use App\Services\ProviderRoutingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProviderRoutingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed SettingWeb for credentials check
        SettingWeb::create([
            'judul_web'      => 'Test Web',
            'username_digi'  => 'demo_digi',
            'api_key_digi'   => 'key_digi',
        ]);
        
        $this->service = new ProviderRoutingService();
    }

    /** @test */
    public function it_prioritizes_provider_path_based_on_priority()
    {
        $layanan = Layanan::factory()->create([
            'provider' => 'manual', // Legacy
            'provider_id' => 'legacy_code'
        ]);

        // Priority 2 (Lower)
        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'digiflazz',
            'provider_sku' => 'SKU_DIGI',
            'priority' => 2,
            'modal_price' => 10000,
            'status' => 'available'
        ]);

        // Priority 1 (Higher)
        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'vip',
            'provider_sku' => 'SKU_VIP',
            'priority' => 1,
            'modal_price' => 12000, // Even if more expensive
            'status' => 'available'
        ]);

        $best = $this->service->findBestProvider($layanan);

        $this->assertEquals('vip', $best['provider_code']);
        $this->assertEquals('SKU_VIP', $best['sku']);
    }

    /** @test */
    public function it_uses_cheapest_price_as_tie_breaker_for_same_priority()
    {
        $layanan = Layanan::factory()->create();

        // Priority 1, Expensive
        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'digiflazz',
            'provider_sku' => 'SKU_DIGI',
            'priority' => 1,
            'modal_price' => 15000,
            'status' => 'available'
        ]);

        // Priority 1, Cheap
        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'bangjeff',
            'provider_sku' => 'SKU_jeff',
            'priority' => 1,
            'modal_price' => 14000,
            'status' => 'available'
        ]);

        $best = $this->service->findBestProvider($layanan);

        $this->assertEquals('bangjeff', $best['provider_code']);
        $this->assertEquals('SKU_jeff', $best['sku']);
    }

    /** @test */
    public function it_skips_unavailable_providers()
    {
        $layanan = Layanan::factory()->create();

        // Priority 1 but Maintenance
        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'digiflazz',
            'provider_sku' => 'SKU_DIGI',
            'priority' => 1, // High priority
            'status' => 'maintenance'
        ]);

        // Priority 2 but Available
        ProviderPath::create([
            'layanan_id' => $layanan->id,
            'provider_code' => 'vip',
            'provider_sku' => 'SKU_VIP',
            'priority' => 2,
            'status' => 'available'
        ]);

        $best = $this->service->findBestProvider($layanan);

        $this->assertEquals('vip', $best['provider_code']);
    }

    /** @test */
    public function it_falls_back_to_legacy_fields_if_no_paths_exist()
    {
        $layanan = Layanan::factory()->create([
            'provider_id' => 'legacy_provider', // treated as code in legacy check? No, provider_id is code usually? 
            // Wait, legacy check uses: $layanan->provider_id and $layanan->provider_nomimal (SKU)
            // But in OrderController legacy was $dataLayanan->provider (code) and $dataLayanan->provider_id (sku)
            // Let's check logic in Service: 
            // if (!empty($layanan->provider_id) && !empty($layanan->provider_nomimal))
            'provider_id' => 'digiflazz', // Code
            'provider_nomimal' => 'SKU_LEGACY' // SKU
        ]);

        // Service logic: Use provider_id as CODE ?? Wait, let's re-read Service.
        // Service Code: 
        // return $this->formatProviderResult($layanan->provider_id, $layanan->provider_nomimal);
        
        $best = $this->service->findBestProvider($layanan);

        $this->assertEquals('digiflazz', $best['provider_code']);
        $this->assertEquals('SKU_LEGACY', $best['sku']);
    }
}
