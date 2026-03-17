<?php

namespace App\Filament\Admin\Resources\Produks\Pages;

use App\Filament\Admin\Resources\Produks\ProdukResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Http\Controllers\DigiFlazzController;
use App\Services\ProductPricingService;

class ListProduks extends ListRecords
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            
            Action::make('sync_digiflazz')
                ->label('Sync DigiFlazz')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    try {
                        $pricing = app(ProductPricingService::class);
                        $digi = new DigiFlazzController;
                        $data = $digi->harga();

                        $products = $data['data'] ?? null;

                        if (is_array($products) && array_is_list($products)) {
                            $updatedCount = 0;

                            foreach ($products as $product) {
                                if (! is_array($product)) {
                                    continue;
                                }

                                $isActive = (bool) ($product['buyer_product_status'] ?? false);
                                $brand = trim((string) ($product['brand'] ?? ''));
                                $providerSku = trim((string) ($product['buyer_sku_code'] ?? ''));
                                $price = $product['price'] ?? null;

                                if (! $isActive || $brand === '' || $providerSku === '' || ! is_numeric($price)) {
                                    continue;
                                }

                                $dataGames = Kategori::where('nama', $brand)->first();
                                $dataProduct = Layanan::where('provider_id', $providerSku)->first();

                                if ($dataGames && $dataProduct) {
                                    $pricing->rebaseFromNewBaseCostKeepingMargins($dataProduct, $price);
                                    $dataProduct->save();

                                    $updatedCount++;
                                }
                            }

                            Notification::make()
                                ->title('DigiFlazz Sync Completed')
                                ->body("Successfully updated {$updatedCount} products from DigiFlazz")
                                ->success()
                                ->send();
                        } else {
                            $message = is_array($products)
                                ? (string) ($products['message'] ?? 'Invalid data received from DigiFlazz API')
                                : 'Invalid data received from DigiFlazz API';

                            Notification::make()
                                ->title('DigiFlazz Sync Failed')
                                ->body($message)
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->body('An error occurred: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Sync DigiFlazz Products')
                ->modalDescription('This will fetch and update all product prices from DigiFlazz API. This may take a few moments.')
                ->modalSubmitActionLabel('Sync Now'),
            
            Action::make('sync_bangjeff')
                ->label('Sync BangJeff')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->form([
                    Select::make('kategori_id')
                        ->label('Target Kategori')
                        ->options(Kategori::pluck('nama', 'id'))
                        ->required()
                        ->searchable(),
                    Toggle::make('update_existing')
                        ->label('Update Existing Products')
                        ->default(false),
                    Toggle::make('auto_activate')
                        ->label('Auto Activate New Products')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    // Simulate API call to BangJeff
                    $this->syncFromBangJeff($data);
                    
                    Notification::make()
                        ->title('BangJeff Sync Started')
                        ->body('Product synchronization from BangJeff has been initiated')
                        ->info()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalDescription('This will fetch products from BangJeff API and sync them to your database.'),
                
            Action::make('sync_topupedia')
                ->label('Sync Topupedia')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->form([
                    Select::make('kategori_id')
                        ->label('Target Kategori')
                        ->options(Kategori::pluck('nama', 'id'))
                        ->required()
                        ->searchable(),
                    Toggle::make('update_existing')
                        ->label('Update Existing Products')
                        ->default(false),
                    Toggle::make('auto_activate')
                        ->label('Auto Activate New Products')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    // Simulate API call to Topupedia
                    $this->syncFromTopupedia($data);
                    
                    Notification::make()
                        ->title('Topupedia Sync Started')
                        ->body('Product synchronization from Topupedia has been initiated')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalDescription('This will fetch products from Topupedia API and sync them to your database.'),
                
            Action::make('bulk_update_prices')
                ->label('Bulk Adjust Tier Prices')
                ->icon('heroicon-o-currency-dollar')
                ->color('warning')
                ->form([
                    Select::make('provider')
                        ->label('Provider')
                        ->options([
                            'digiflazz' => 'Digiflazz',
                            'apigames' => 'API Games',
                            'vip' => 'VIP Reseller',
                            'bangjeff' => 'BangJeff',
                            'topupedia' => 'Topupedia',
                        ])
                        ->required(),
                    Select::make('price_adjustment')
                        ->label('Price Adjustment')
                        ->options([
                            '5' => '+5%',
                            '10' => '+10%',
                            '15' => '+15%',
                            '-5' => '-5%',
                            '-10' => '-10%',
                        ])
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->bulkUpdatePrices($data);
                    
                    Notification::make()
                        ->title('Bulk Tier Price Update')
                        ->body('Tier selling prices have been updated for selected provider')
                        ->warning()
                        ->send();
                })
                ->requiresConfirmation(),

            Action::make('rebase_tier_prices')
                ->label('Rebase Tier Prices')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('danger')
                ->form([
                    Select::make('provider')
                        ->label('Provider')
                        ->options([
                            'digiflazz' => 'Digiflazz',
                            'apigames' => 'API Games',
                            'vip' => 'VIP Reseller',
                            'bangjeff' => 'BangJeff',
                            'topupedia' => 'Topupedia',
                            'manual' => 'Manual',
                        ])
                        ->placeholder('Semua provider'),
                    Select::make('kategori_id')
                        ->label('Kategori')
                        ->options(Kategori::pluck('nama', 'id'))
                        ->searchable()
                        ->placeholder('Semua kategori'),
                ])
                ->action(function (array $data) {
                    $updatedCount = $this->rebaseTierPrices($data);

                    Notification::make()
                        ->title('Rebase selesai')
                        ->body("Berhasil rebase {$updatedCount} produk berdasarkan profit persen yang tersimpan.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Rebase Tier Prices')
                ->modalDescription('Action ini akan menghitung ulang harga member, platinum, dan gold dari harga modal sekarang berdasarkan profit persen yang tersimpan di masing-masing produk.')
                ->modalSubmitActionLabel('Rebase Sekarang'),
        ];
    }
    
    private function syncFromBangJeff(array $data): void
    {
        // Simulate BangJeff API sync
        // In real implementation, you would:
        // 1. Call BangJeff API
        // 2. Parse response
        // 3. Create/update products
        // 4. Handle errors
        
        // Mock implementation
        sleep(1); // Simulate API delay
        
        // Example: Create sample products
        $sampleProducts = [
            [
                'layanan' => 'Mobile Legends 5 Diamond',
                'provider_id' => 'BANGJEFF_ML_5',
                'kategori_id' => $data['kategori_id'],
                'provider' => 'bangjeff',
                'harga' => 1500,
                'harga_member' => 1600,
                'harga_platinum' => 1650,
                'harga_gold' => 1700,
                'status' => $data['auto_activate'] ? 'active' : 'inactive',
                'catatan' => 'Synced from BangJeff API',
            ],
        ];
        
        foreach ($sampleProducts as $productData) {
            \App\Models\Produk::updateOrCreate(
                ['provider_id' => $productData['provider_id']],
                $productData
            );
        }
    }
    
    private function syncFromTopupedia(array $data): void
    {
        // Simulate Topupedia API sync
        sleep(1); // Simulate API delay
        
        // Example: Create sample products
        $sampleProducts = [
            [
                'layanan' => 'Free Fire 70 Diamond',
                'provider_id' => 'TOPUPEDIA_FF_70',
                'kategori_id' => $data['kategori_id'],
                'provider' => 'topupedia',
                'harga' => 10000,
                'harga_member' => 11000,
                'harga_platinum' => 11200,
                'harga_gold' => 11500,
                'status' => $data['auto_activate'] ? 'active' : 'inactive',
                'catatan' => 'Synced from Topupedia API',
            ],
        ];
        
        foreach ($sampleProducts as $productData) {
            \App\Models\Produk::updateOrCreate(
                ['provider_id' => $productData['provider_id']],
                $productData
            );
        }
    }
    
    private function bulkUpdatePrices(array $data): void
    {
        $provider = $data['provider'];
        $adjustment = (int) $data['price_adjustment'];
        
        \App\Models\Produk::where('provider', $provider)
            ->each(function ($product) use ($adjustment) {
                $pricing = app(ProductPricingService::class);
                $pricing->adjustSellingPricesByPercent($product, $adjustment);
                $product->save();
            });
    }

    private function rebaseTierPrices(array $data): int
    {
        $pricing = app(ProductPricingService::class);

        $query = Layanan::query()
            ->when(
                filled($data['provider'] ?? null),
                fn ($query) => $query->where('provider', $data['provider'])
            )
            ->when(
                filled($data['kategori_id'] ?? null),
                fn ($query) => $query->where('kategori_id', $data['kategori_id'])
            );

        $updatedCount = 0;

        $query->each(function (Layanan $product) use ($pricing, &$updatedCount) {
            $pricing->rebaseFromNewBaseCostKeepingMargins($product, $product->harga);
            $product->save();
            $updatedCount++;
        });

        return $updatedCount;
    }
}
