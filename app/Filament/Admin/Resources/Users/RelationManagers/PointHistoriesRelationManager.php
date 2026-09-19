<?php

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Models\PointHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PointHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'pointHistories';

    protected static ?string $title = 'Riwayat Poin';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'earn' => 'Masuk',
                        'redeem' => 'Keluar',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'earn' => 'success',
                        'redeem' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('points')
                    ->label('Poin')
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn (PointHistory $record): string => $record->type === 'earn' ? 'success' : 'danger')
                    ->state(fn (PointHistory $record): string => ($record->type === 'earn' ? '+' : '-') . number_format((int) $record->points, 0, ',', '.')),
                TextColumn::make('description')
                    ->label('Keterangan')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('-'),
                TextColumn::make('order_id')
                    ->label('Order ID')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
