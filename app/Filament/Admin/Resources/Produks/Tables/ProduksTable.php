<?php

namespace App\Filament\Admin\Resources\Produks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
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
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ProduksTable
{
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
                    ->options(Kategori::pluck('nama', 'id'))
                    ->searchable()
                    ->preload(),
                    
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
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('toggle_status')
                    ->label(fn ($record) => $record->status === 'active' ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->status === 'active' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->status === 'active' ? 'danger' : 'success')
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
}
