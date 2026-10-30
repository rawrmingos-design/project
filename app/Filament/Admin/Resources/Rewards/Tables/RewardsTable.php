<?php

namespace App\Filament\Admin\Resources\Rewards\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RewardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->disk('public')
                    ->height(45)
                    ->square(),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->category),

                TextColumn::make('point_cost')
                    ->label('Harga Poin')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->suffix(' poin')
                    ->color('primary'),

                TextColumn::make('stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($state) => match (true) {
                        $state == 0 => 'danger',
                        $state <= 5 => 'warning',
                        default     => 'success',
                    })
                    ->weight(fn ($state) => $state <= 5 ? 'bold' : null),

                TextColumn::make('min_tier')
                    ->label('Tier')
                    ->badge()
                    ->placeholder('Semua')
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),

                Filter::make('out_of_stock')
                    ->label('Stok Habis')
                    ->query(fn (Builder $query): Builder => $query->where('stock', 0)),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('add_stock')
                    ->label('Tambah Stok')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Jumlah')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(10),
                    ])
                    ->action(function ($record, array $data) {
                        $record->increment('stock', (int) $data['amount']);

                        Notification::make()
                            ->title('Stok ditambah')
                            ->body("+{$data['amount']} stok untuk: {$record->name}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
