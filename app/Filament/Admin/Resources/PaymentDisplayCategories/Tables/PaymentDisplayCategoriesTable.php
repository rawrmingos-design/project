<?php

namespace App\Filament\Admin\Resources\PaymentDisplayCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use App\Support\PaymentCatalogAccess;
use App\Models\TenantPaymentDisplayCategorySetting;

class PaymentDisplayCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('display_style')
                    ->label('Display Style')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'flat' => 'info',
                        'accordion' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Sort Order')
                    ->sortable(),

                IconColumn::make('is_visible')
                    ->label(PaymentCatalogAccess::isMaster() ? 'Global Status' : 'Storefront')
                    ->boolean()
                    ->sortable()
                    ->visible(PaymentCatalogAccess::isMaster()),

                ToggleColumn::make('storefront_visible')
                    ->label('Storefront Status')
                    ->visible(! PaymentCatalogAccess::isMaster())
                    ->getStateUsing(function ($record) {
                        if (! $record->is_visible) {
                            return false;
                        }

                        $setting = TenantPaymentDisplayCategorySetting::query()
                            ->where('tenant_id', PaymentCatalogAccess::currentTenantId())
                            ->where('payment_display_category_id', $record->id)
                            ->first();

                        return $setting ? $setting->is_visible : true; // Default visible if globally visible and no override
                    })
                    ->updateStateUsing(function ($record, $state) {
                        TenantPaymentDisplayCategorySetting::query()->updateOrCreate(
                            [
                                'tenant_id' => PaymentCatalogAccess::currentTenantId(),
                                'payment_display_category_id' => $record->id,
                            ],
                            ['is_visible' => $state]
                        );
                    })
                    ->disabled(fn ($record) => ! $record->is_visible)
                    ->tooltip(fn ($record) => ! $record->is_visible ? 'Kategori dinonaktifkan oleh master admin.' : 'Toggle tampilan kategori ini di storefront Anda.'),

                TextColumn::make('icon')
                    ->label('Icon')
                    ->placeholder('—'),

                TextColumn::make('methods_count')
                    ->counts('methods')
                    ->label('Methods'),
            ])
            ->filters([
                SelectFilter::make('is_visible')
                    ->label('Visibility')
                    ->options([
                        1 => 'Visible only',
                        0 => 'Hidden only',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->toolbarActions(PaymentCatalogAccess::isMaster() ? [
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ] : [])
            ->defaultSort('sort_order', 'asc');
    }
}
