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

class ListProduks extends ListRecords
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            
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
                ->label('Bulk Update Prices')
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
                    Select::make('profit_adjustment')
                        ->label('Profit Adjustment')
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
                        ->title('Bulk Price Update')
                        ->body('Prices have been updated for selected provider')
                        ->warning()
                        ->send();
                })
                ->requiresConfirmation(),
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
                'harga_member' => 1400,
                'harga_platinum' => 1350,
                'harga_gold' => 1300,
                'profit' => 10,
                'profit_member' => 8,
                'profit_platinum' => 6,
                'profit_gold' => 5,
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
                'harga_member' => 9500,
                'harga_platinum' => 9200,
                'harga_gold' => 9000,
                'profit' => 12,
                'profit_member' => 10,
                'profit_platinum' => 8,
                'profit_gold' => 6,
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
        $adjustment = (int) $data['profit_adjustment'];
        
        \App\Models\Produk::where('provider', $provider)
            ->each(function ($product) use ($adjustment) {
                $newProfit = max(1, $product->profit + $adjustment);
                $product->update(['profit' => $newProfit]);
            });
    }
}
