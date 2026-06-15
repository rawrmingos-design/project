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
use App\Services\Providers\BangJeffService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
                            $fetchedCount = 0;
                            $updatedCount = 0;
                            $skippedInactiveCount = 0;
                            $skippedInvalidCount = 0;
                            $skippedNoCategoryCount = 0;
                            $skippedNoLocalProductCount = 0;

                            foreach ($products as $product) {
                                if (! is_array($product)) {
                                    $skippedInvalidCount++;
                                    continue;
                                }

                                $fetchedCount++;

                                $isActive = (bool) ($product['buyer_product_status'] ?? false);
                                $brand = trim((string) ($product['brand'] ?? ''));
                                $providerSku = trim((string) ($product['buyer_sku_code'] ?? ''));
                                $price = $product['price'] ?? null;

                                if (! $isActive) {
                                    $skippedInactiveCount++;
                                    continue;
                                }

                                if ($brand === '' || $providerSku === '' || ! is_numeric($price)) {
                                    $skippedInvalidCount++;
                                    continue;
                                }

                                $dataGames = Kategori::where('nama', $brand)->first();

                                if (! $dataGames) {
                                    $skippedNoCategoryCount++;
                                    continue;
                                }

                                $dataProduct = Layanan::query()
                                    ->where('provider', 'digiflazz')
                                    ->where('provider_id', $providerSku)
                                    ->first();

                                if (! $dataProduct) {
                                    $skippedNoLocalProductCount++;
                                    continue;
                                }

                                $pricing->rebaseFromNewBaseCostKeepingMargins($dataProduct, $price);
                                $dataProduct->save();

                                $updatedCount++;
                            }

                            Log::info('DigiFlazz product sync completed', [
                                'fetched' => $fetchedCount,
                                'updated' => $updatedCount,
                                'skipped_inactive' => $skippedInactiveCount,
                                'skipped_invalid' => $skippedInvalidCount,
                                'skipped_no_category' => $skippedNoCategoryCount,
                                'skipped_no_local_product' => $skippedNoLocalProductCount,
                            ]);

                            Notification::make()
                                ->title('DigiFlazz Sync Completed')
                                ->body("Fetched products: {$fetchedCount} | Updated: {$updatedCount} | Skipped inactive: {$skippedInactiveCount} | Skipped invalid: {$skippedInvalidCount} | No category: {$skippedNoCategoryCount} | Not found: {$skippedNoLocalProductCount}")
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
                    Toggle::make('refresh_products_cache')
                        ->label('Refresh Produk BangJeff dari API sebelum sync')
                        ->helperText('Aktifkan kalau list Produk BangJeff di dropdown belum update. Cache produk akan dihapus dan diambil ulang saat submit.')
                        ->default(false),
                    Select::make('bangjeff_product_code')
                        ->label('Produk BangJeff')
                        ->options(fn (): array => $this->getCachedBangJeffProductOptions())
                        ->required()
                        ->searchable(),
                    Select::make('kategori_id')
                        ->label('Kategori Lokal')
                        ->options(Kategori::pluck('nama', 'id'))
                        ->required()
                        ->searchable(),
                    Toggle::make('update_existing')
                        ->label('Update Existing Products')
                        ->helperText('Fase ini hanya update produk BangJeff yang sudah ada di kategori lokal pilihan. Variant yang belum ada akan diskip.')
                        ->default(true)
                        ->disabled(),
                ])
                ->action(function (array $data) {
                    try {
                        $summary = $this->syncFromBangJeff($data);

                        Notification::make()
                            ->title('BangJeff Sync Completed')
                            ->body("Fetched variants: {$summary['fetched']} | Updated: {$summary['updated']} | Skipped inactive: {$summary['skipped_inactive']} | Skipped invalid: {$summary['skipped_invalid']} | Skipped not found: {$summary['skipped_not_found']}")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('BangJeff Sync Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Sync BangJeff Products')
                ->modalDescription('Pilih Produk BangJeff dari API dan Kategori Lokal untuk update banyak produk BangJeff existing dalam kategori tersebut. Tidak membuat produk baru.')
                ->modalSubmitActionLabel('Sync Now'),
                
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
    
    private function syncFromBangJeff(array $data): array
    {
        $productCode = trim((string) ($data['bangjeff_product_code'] ?? ''));
        $kategoriId = $data['kategori_id'] ?? null;

        $summary = [
            'fetched' => 0,
            'updated' => 0,
            'skipped_inactive' => 0,
            'skipped_invalid' => 0,
            'skipped_not_found' => 0,
        ];

        if ($productCode === '' || blank($kategoriId)) {
            throw new \InvalidArgumentException('Produk BangJeff dan Kategori Lokal wajib dipilih.');
        }

        $productOptions = $this->getCachedBangJeffProductOptions((bool) ($data['refresh_products_cache'] ?? false));

        if (! array_key_exists($productCode, $productOptions)) {
            throw new \RuntimeException('Produk BangJeff yang dipilih tidak ditemukan di cache/API. Coba aktifkan refresh cache lalu sync ulang.');
        }

        $response = app(BangJeffService::class)->listVariant($productCode);

        if (($response['error'] ?? false) === true || (($response['rc'] ?? null) && $response['rc'] !== '00')) {
            throw new \RuntimeException((string) ($response['message'] ?? 'Gagal mengambil variant BangJeff.'));
        }

        $variants = $response['data'] ?? [];

        if (! is_array($variants)) {
            throw new \RuntimeException('Response variant BangJeff tidak valid.');
        }

        $pricing = app(ProductPricingService::class);

        foreach ($variants as $variant) {
            if (! is_array($variant)) {
                $summary['skipped_invalid']++;
                continue;
            }

            $summary['fetched']++;

            $variantCode = trim((string) ($variant['code'] ?? $variant['variantCode'] ?? ''));
            $rawStatus = strtoupper((string) ($variant['status'] ?? ''));
            $price = $variant['price']['value'] ?? $variant['price'] ?? null;

            if ($variantCode === '' || ! is_numeric($price)) {
                $summary['skipped_invalid']++;
                continue;
            }

            if ($rawStatus !== 'ACTIVE') {
                $summary['skipped_inactive']++;
                continue;
            }

            $product = Layanan::query()
                ->where('provider', 'bangjeff')
                ->where('provider_id', $variantCode)
                ->where('kategori_id', $kategoriId)
                ->first();

            if (! $product) {
                $summary['skipped_not_found']++;
                continue;
            }

            $pricing->rebaseFromNewBaseCostKeepingMargins($product, $price);
            $product->save();

            $summary['updated']++;
        }

        Log::info('BangJeff product sync completed', [
            'product_code' => $productCode,
            'kategori_id' => $kategoriId,
            'fetched' => $summary['fetched'],
            'updated' => $summary['updated'],
            'skipped_inactive' => $summary['skipped_inactive'],
            'skipped_invalid' => $summary['skipped_invalid'],
            'skipped_not_found' => $summary['skipped_not_found'],
        ]);

        return $summary;
    }

    private function getCachedBangJeffProductOptions(bool $refresh = false): array
    {
        $cacheKey = 'bangjeff.products.ID';

        if ($refresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, now()->addHours(6), function (): array {
            $products = app(BangJeffService::class)->getProducts();

            if (! is_array($products) || (($products['success'] ?? true) === false)) {
                Log::warning('BangJeff product options cache skipped: invalid product response.');

                return [];
            }

            return collect($products)
                ->filter(fn ($product): bool => is_array($product))
                ->filter(fn (array $product): bool => ($product['status'] ?? null) === 'available')
                ->filter(fn (array $product): bool => filled($product['provider_id'] ?? null) && filled($product['name'] ?? null))
                ->mapWithKeys(fn (array $product): array => [
                    (string) $product['provider_id'] => sprintf('%s - %s', $product['provider_id'], $product['name']),
                ])
                ->toArray();
        });
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
