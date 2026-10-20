<?php

namespace App\Filament\Admin\Resources\TierPricing\Tables;

use App\Models\TierPricing;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TierPricingTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Paket')
                    ->getStateUsing(fn (TierPricing $record): string => $record->label ?: ucfirst($record->tier))
                    ->description(fn (TierPricing $record): string => $record->tier)
                    ->searchable(['tier', 'label'])
                    ->sortable(['sort_order']),

                TextColumn::make('platform_markup_percent')
                    ->label('Markup Platform')
                    ->suffix('%')
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->tooltip('Markup di atas harga pokok layanan. Menentukan harga beli tenant.'),

                TextColumn::make('tenant_markup_default')
                    ->label('Default Tenant')
                    ->suffix('%')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('price_3_month')
                    ->label('3 Bulan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->description(fn (TierPricing $record): string => $record->strikePriceForDuration(3) > 0
                        ? 'coret ' . number_format($record->strikePriceForDuration(3), 0, ',', '.')
                        : ''),

                TextColumn::make('price_12_month')
                    ->label('12 Bulan')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->description(fn (TierPricing $record): string => $record->strikePriceForDuration(12) > 0
                        ? 'coret ' . number_format($record->strikePriceForDuration(12), 0, ',', '.')
                        : ''),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ]);
    }
}
