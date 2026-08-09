<?php

namespace Tests\Feature;

use App\Models\Layanan;
use App\Models\Provider;
use App\Models\ProviderPath;
use App\Services\ProviderRoutingService;
use App\Support\ProviderRetirement;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProviderRetirementGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_retirement_registry_preserves_retained_and_internal_provider_contracts(): void
    {
        $this->assertTrue(ProviderRetirement::isRetired(' TOPUPEDIA '));
        $this->assertFalse(ProviderRetirement::isRetired('digiflazz'));
        $this->assertTrue(ProviderRetirement::isInternal('jokigendong'));
        $this->assertSame('vip', ProviderRetirement::canonicalCode('VIP_RESELLER'));
    }

    public function test_retired_provider_cannot_be_activated_through_the_model(): void
    {
        $provider = Provider::query()->create([
            'code' => 'topupedia',
            'name' => 'Historical Topupedia',
            'is_active' => false,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Retired providers cannot be activated.');

        $provider->update(['is_active' => true]);
    }

    public function test_retired_layanan_and_provider_paths_cannot_be_made_available(): void
    {
        $layanan = $this->createLayanan('topupedia', 'unavailable');
        $path = $this->createProviderPath($layanan, 'topupedia', 'unavailable');

        try {
            $layanan->update(['status' => 'available']);
            $this->fail('Retired layanan was activated.');
        } catch (DomainException $exception) {
            $this->assertSame('Layanan using a retired provider must remain unavailable.', $exception->getMessage());
        }

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Retired provider paths must remain unavailable.');

        $path->update(['status' => 'available']);
    }

    public function test_routing_skips_retired_path_and_selects_retained_fallback(): void
    {
        $layanan = $this->createLayanan('manual');
        DB::table('provider_paths')->insert([
            'layanan_id' => $layanan->getKey(),
            'provider_code' => 'topupedia',
            'provider_sku' => 'SKU-TOPUPEDIA',
            'modal_price' => 9000,
            'priority' => 1,
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createProviderPath($layanan, 'bangjeff', 'available', priority: 2);

        $result = app(ProviderRoutingService::class)->findBestProvider($layanan->fresh());

        $this->assertSame('bangjeff', $result['provider_code']);
        $this->assertSame('SKU-BANGJEFF', $result['sku']);
    }

    public function test_explicit_retired_and_inactive_routes_are_rejected_but_retained_contracts_remain(): void
    {
        Provider::query()
            ->where('code', 'sufpayment')
            ->update(['is_active' => false]);

        $service = app(ProviderRoutingService::class);

        $this->assertNull($service->resolveExplicitProvider('topupedia', 'OLD-SKU'));
        $this->assertNull($service->resolveExplicitProvider('sufpayment', 'SUF-SKU'));
        $this->assertSame('vip_reseller', $service->resolveExplicitProvider('vip_reseller', 'VIP-SKU')['provider_code']);
        $this->assertSame('jokigendong', $service->resolveExplicitProvider('jokigendong', 'INTERNAL')['provider_code']);
    }

    public function test_routable_options_exclude_retired_and_inactive_providers_case_insensitively(): void
    {
        Provider::query()->insert([
            [
                'code' => 'TOPUPEDIA',
                'name' => 'Historical Topupedia',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'digiflazz',
                'name' => 'Digiflazz',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'bangjeff',
                'name' => 'BangJeff',
                'is_active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $options = Provider::routableOptions();

        $this->assertSame('Digiflazz', $options['digiflazz'] ?? null);
        $this->assertArrayNotHasKey('TOPUPEDIA', $options);
        $this->assertArrayNotHasKey('bangjeff', $options);
    }

    private function createLayanan(string $provider, string $status = 'available'): Layanan
    {
        return Layanan::query()->create([
            'kategori_id' => 1,
            'layanan' => 'Retirement Test Product ' . uniqid(),
            'provider_id' => 'SKU-' . strtoupper($provider),
            'harga' => 15000,
            'harga_member' => 15500,
            'harga_platinum' => 15400,
            'harga_gold' => 15300,
            'profit_member' => 500,
            'profit_platinum' => 400,
            'profit_gold' => 300,
            'status' => $status,
            'provider' => $provider,
            'catatan' => '',
            'is_flash_sale' => false,
        ]);
    }

    private function createProviderPath(Layanan $layanan, string $provider, string $status, int $priority = 1): ProviderPath
    {
        return ProviderPath::query()->create([
            'layanan_id' => $layanan->getKey(),
            'provider_code' => $provider,
            'provider_sku' => 'SKU-' . strtoupper($provider),
            'modal_price' => 10000,
            'priority' => $priority,
            'status' => $status,
        ]);
    }
}
