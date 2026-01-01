<?php

namespace App\Filament\Admin\Resources\Vouchers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class VouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Voucher Code')
                    ->searchable()
                    ->copyable()
                    ->weight('bold')
                    ->color('primary'),
                    
                TextColumn::make('promo')
                    ->label('Discount')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($state) => match(true) {
                        $state >= 50 => 'danger',
                        $state >= 25 => 'warning',
                        default => 'success',
                    }),
                    
                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($state) => match(true) {
                        $state == 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->weight(fn ($state) => $state <= 10 ? 'bold' : null),
                    
                TextColumn::make('mintrx')
                    ->label('Min. Transaction')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),
                    
                TextColumn::make('max_potongan')
                    ->label('Max. Discount')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd(),
                    
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock', '<=', 10)),
                    
                Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn (Builder $query): Builder => $query->where('stock', 0)),
            ])
            ->recordActions([
                EditAction::make(),
                
                Action::make('add_stock')
                    ->label('Add Stock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Amount to Add')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(10),
                    ])
                    ->action(function ($record, array $data) {
                        $record->stock += $data['amount'];
                        $record->save();
                        
                        Notification::make()
                            ->title('Stock Added')
                            ->body("Added {$data['amount']} stock to voucher: {$record->kode}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
