<?php

namespace App\Filament\Admin\Resources\Produks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use App\Models\Kategori;
use App\Http\Controllers\DigiFlazzController;
use App\Services\ProductPricingService;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class ProduksTable
{
    private static function getCachedKategoriOptions(): array
    {
        return Cache::remember('admin:produk:kategori-options', now()->addMinutes(15), function (): array {
            return Kategori::query()
                ->orderBy('nama')
                ->pluck('nama', 'id')
                ->toArray();
        });
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('layanan')
                    ->label('Nama Produk')
                    ->prefix('📦 ')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('kategori.nama')
                    ->label('Kategori')
                    ->prefix('🎮 ')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('provider_id')
                    ->label('Provider ID')
                    ->prefix('🔌 ')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Provider ID copied')
                    ->copyMessageDuration(1500),

                BadgeColumn::make('provider')
                    ->label('Provider')
                    ->colors([
                        'primary' => 'digiflazz',
                        'success' => 'apigames',
                        'warning' => 'vip',
                        'info' => 'bangjeff',
                        'secondary' => 'topupedia',
                        'danger' => 'manual',
                    ]),

                TextColumn::make('harga')
                    ->label('Harga Modal')
                    ->prefix('💰 ')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),

                TextColumn::make('harga_member')
                    ->label('Harga Member / Publik')
                    ->prefix('💲 ')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('harga_platinum')
                    ->label('Harga Platinum')
                    ->prefix('🤵 ')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('harga_gold')
                    ->label('Harga Gold')
                    ->prefix('👑 ')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('harga_flash_sale')
                    ->label('Harga Flash Sale')
                    ->prefix('⚡ ')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('danger')
                    ->weight('bold'),

                IconColumn::make('is_flash_sale')
                    ->label('Flash Sale')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->falseColor('gray'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'maintenance',
                        'secondary' => 'out_of_stock',
                    ]),

                TextColumn::make('judul_flash_sale')
                    ->label('Judul Flash Sale')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('N/A'),

                TextColumn::make('stock_flash_sale')
                    ->label('Stock Flash Sale')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('warning'),

                TextColumn::make('expired_flash_sale')
                    ->label('Expired Flash Sale')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('danger'),

                TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('No notes')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(fn (): array => static::getCachedKategoriOptions())
                    ->searchable(),

                SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        'digiflazz' => 'Digiflazz',
                        'apigames' => 'API Games',
                        'vip' => 'VIP Reseller',
                        'bangjeff' => 'BangJeff',
                        'topupedia' => 'Topupedia',
                        'manual' => 'Manual',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'maintenance' => 'Maintenance',
                        'out_of_stock' => 'Out of Stock',
                    ]),

                TernaryFilter::make('is_flash_sale')
                    ->label('Flash Sale')
                    ->placeholder('All products')
                    ->trueLabel('Flash Sale Only')
                    ->falseLabel('Regular Products'),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Created from'),
                        DatePicker::make('created_until')
                            ->label('Created until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat'),
                    EditAction::make()
                        ->label('Ubah'),
                    Action::make('sync_price')
                        ->label('Sync Harga')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->visible(fn($record): bool => $record->provider === 'digiflazz')
                        ->action(function ($record) {
                            try {
                                $summary = self::syncDigiflazzRecordPrice($record);

                                Notification::make()
                                    ->title('Harga DigiFlazz Berhasil Disync')
                                    ->body("{$record->layanan}: {$summary['old_price']} → {$summary['new_price']}")
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Sync Harga Gagal')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Sync Harga DigiFlazz')
                        ->modalDescription('Action ini hanya mengambil harga terbaru untuk 1 produk DigiFlazz ini, lalu rebase tier price berdasarkan margin yang tersimpan.')
                        ->modalSubmitActionLabel('Sync Harga'),
                    Action::make('toggle_status')
                        ->label(fn($record) => $record->status === 'active' ? 'Deactivate' : 'Activate')
                        ->icon(fn($record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn($record) => $record->status === 'active' ? 'danger' : 'success')
                        ->action(function ($record) {
                            $newStatus = $record->status === 'active' ? 'inactive' : 'active';
                            $record->update(['status' => $newStatus]);

                            Notification::make()
                                ->title('Status Updated')
                                ->body("Product status changed to {$newStatus}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ])
                    ->label('')
                    ->tooltip('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button(),
            ])
            ->recordActionsColumnLabel('Actions')
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    \Filament\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['status' => 'active']);

                            Notification::make()
                                ->title('Products Activated')
                                ->body("{$count} products have been activated")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['status' => 'inactive']);

                            Notification::make()
                                ->title('Products Deactivated')
                                ->body("{$count} products have been deactivated")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('maintenance')
                        ->label('Set Maintenance')
                        ->icon('heroicon-o-wrench-screwdriver')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['status' => 'maintenance']);

                            Notification::make()
                                ->title('Products Set to Maintenance')
                                ->body("{$count} products have been set to maintenance mode")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    private static function syncDigiflazzRecordPrice($record): array
    {
        if ($record->provider !== 'digiflazz') {
            throw new \RuntimeException('Sync harga per produk saat ini hanya tersedia untuk provider DigiFlazz.');
        }

        $providerSku = trim((string) ($record->provider_id ?? ''));

        if ($providerSku === '') {
            throw new \RuntimeException('Provider ID produk kosong, tidak bisa sync harga DigiFlazz.');
        }

        $response = (new DigiFlazzController)->harga();
        $products = $response['data'] ?? null;

        if (! is_array($products) || ! array_is_list($products)) {
            throw new \RuntimeException('Response price list DigiFlazz tidak valid.');
        }

        $matchedProduct = collect($products)->first(function ($product) use ($providerSku): bool {
            return is_array($product)
                && trim((string) ($product['buyer_sku_code'] ?? '')) === $providerSku;
        });

        if (! is_array($matchedProduct)) {
            throw new \RuntimeException("SKU DigiFlazz {$providerSku} tidak ditemukan di price list.");
        }

        $isActive = (bool) ($matchedProduct['buyer_product_status'] ?? false);
        $newPrice = $matchedProduct['price'] ?? null;

        if (! $isActive) {
            throw new \RuntimeException("SKU DigiFlazz {$providerSku} sedang tidak aktif dari provider.");
        }

        if (! is_numeric($newPrice)) {
            throw new \RuntimeException("Harga DigiFlazz untuk SKU {$providerSku} tidak valid.");
        }

        $oldPrice = (int) ($record->harga ?? 0);

        app(ProductPricingService::class)->rebaseFromNewBaseCostKeepingMargins($record, $newPrice);
        $record->save();

        return [
            'old_price' => self::formatRupiah($oldPrice),
            'new_price' => self::formatRupiah((int) $newPrice),
        ];
    }

    private static function formatRupiah(int|float $amount): string
    {
        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }
}
