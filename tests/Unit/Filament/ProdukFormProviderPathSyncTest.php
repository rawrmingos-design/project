<?php

namespace Tests\Unit\Filament;

use App\Filament\Admin\Resources\Produks\Schemas\ProdukForm;
use ReflectionClass;
use Tests\TestCase;

class ProdukFormProviderPathSyncTest extends TestCase
{
    public function test_it_updates_existing_provider_path_without_creating_duplicates(): void
    {
        $state = [
            'provider_paths' => [
                [
                    'id' => 99,
                    'provider_code' => 'apigames',
                    'provider_sku' => 'OLD-SKU',
                    'modal_price' => 12000,
                    'priority' => 2,
                    'status' => 'inactive',
                ],
                [
                    'provider_code' => 'vip',
                    'provider_sku' => 'VIP-1',
                    'modal_price' => 11000,
                    'priority' => 1,
                    'status' => 'available',
                ],
            ],
        ];

        $this->invokeSyncSuggestedProviderPath($state, 'apigames', 'NEW-SKU', 15000, 'available', [
            'source' => 'apigames_catalog',
            'summary' => 'Catalog Product ID: 123',
        ]);

        $this->assertCount(2, $state['provider_paths']);
        $this->assertSame(99, $state['provider_paths'][0]['id']);
        $this->assertSame('NEW-SKU', $state['provider_paths'][0]['provider_sku']);
        $this->assertSame(15000, $state['provider_paths'][0]['modal_price']);
        $this->assertSame(2, $state['provider_paths'][0]['priority']);
        $this->assertSame('available', $state['provider_paths'][0]['status']);
        $this->assertSame('apigames_catalog', $state['provider_paths'][0]['metadata']['source']);
    }

    public function test_it_appends_new_provider_path_with_next_priority(): void
    {
        $state = [
            'provider_paths' => [
                [
                    'provider_code' => 'digiflazz',
                    'provider_sku' => 'DG-1',
                    'modal_price' => 10000,
                    'priority' => 1,
                    'status' => 'available',
                ],
                [
                    'provider_code' => 'vip',
                    'provider_sku' => 'VIP-1',
                    'modal_price' => 11000,
                    'priority' => 3,
                    'status' => 'available',
                ],
            ],
        ];

        $this->invokeSyncSuggestedProviderPath($state, 'apigames', 'AG-NEW', 9000, 'available', [
            'source' => 'apigames_catalog',
            'summary' => 'Catalog Product ID: 999',
        ]);

        $this->assertCount(3, $state['provider_paths']);
        $appended = $state['provider_paths'][2];

        $this->assertSame('apigames', $appended['provider_code']);
        $this->assertSame('AG-NEW', $appended['provider_sku']);
        $this->assertSame(9000, $appended['modal_price']);
        $this->assertSame(4, $appended['priority']);
        $this->assertSame('available', $appended['status']);
        $this->assertSame('Catalog Product ID: 999', $appended['metadata']['summary']);
    }

    private function invokeSyncSuggestedProviderPath(array &$state, string $providerCode, string $providerSku, int $modalPrice, string $status, array $metadata): void
    {
        $reflection = new ReflectionClass(ProdukForm::class);
        $method = $reflection->getMethod('syncSuggestedProviderPath');
        $method->setAccessible(true);

        $set = function (string $key, mixed $value) use (&$state): void {
            $state[$key] = $value;
        };

        $get = function (string $key) use (&$state): mixed {
            return $state[$key] ?? null;
        };

        $method->invoke(null, $providerCode, $providerSku, $modalPrice, $status, $metadata, $set, $get);
    }
}
