<?php

namespace App\Filament\Admin\Resources\Methods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use App\Support\PaymentCatalogAccess;
use App\Models\TenantPaymentMethodSetting;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;

class MethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->label('Logo')
                    ->disk(config('uploads.disk', 'assets'))
                    ->getStateUsing(fn ($record): string => ltrim((string) ($record->getRawOriginal('images') ?: $record->images ?: ''), '/'))
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(asset('assets/logo/favicon.webp')),
                    
                TextColumn::make('name')
                    ->label('Nama Metode')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                BadgeColumn::make('tipe')
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'bank',
                        'success' => 'e-walet',
                        'warning' => 'qris',
                        'info' => 'virtual-account',
                        'secondary' => 'convenience-store',
                        'danger' => 'SALDO',
                    ]),
                    
                BadgeColumn::make('payment')
                    ->label('Gateway')
                    ->colors([
                        'primary' => 'tripay',
                        'success' => 'tokopay',
                        'warning' => 'paydisini',
                        'secondary' => 'manual',
                        'danger' => 'duitku',
                    ]),
                    
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->tooltip(fn ($record): ?string => filled($record->keterangan) ? (string) $record->keterangan : null)
                    ->toggleable(),
                    
                TextColumn::make('fee_percent')
                    ->label('Fee %')
                    ->suffix('%')
                    ->sortable(),
                    
                TextColumn::make('fix_fee')
                    ->label('Fix Fee')
                    ->money('IDR')
                    ->sortable(),
                    
                TextColumn::make('min_pembelian')
                    ->label('Min')
                    ->money('IDR')
                    ->sortable(),
                    
                TextColumn::make('max_pembelian')
                    ->label('Max')
                    ->money('IDR')
                    ->sortable(),
                    
                TextColumn::make('statuspayment')
                    ->label(PaymentCatalogAccess::isMaster() ? 'Global Status' : 'Storefront')
                    ->badge()
                    ->formatStateUsing(function (?bool $state) {
                        return $state ? 'Aktif' : 'Nonaktif';
                    })
                    ->color(function (?bool $state) {
                        return $state ? 'success' : 'danger';
                    })
                    ->tooltip(function (?bool $state) {
                        return $state
                            ? 'Metode pembayaran aktif secara global.'
                            : 'Metode pembayaran nonaktif secara global.';
                    })
                    ->visible(PaymentCatalogAccess::isMaster()),

                ToggleColumn::make('storefront_visible')
                    ->label('Storefront Status')
                    ->visible(! PaymentCatalogAccess::isMaster())
                    ->getStateUsing(function ($record) {
                        if (! $record->statuspayment) {
                            return false;
                        }

                        $setting = TenantPaymentMethodSetting::query()
                            ->where('tenant_id', PaymentCatalogAccess::currentTenantId())
                            ->where('method_id', $record->id)
                            ->first();

                        return $setting ? $setting->is_visible : true;
                    })
                    ->updateStateUsing(function ($record, $state) {
                        TenantPaymentMethodSetting::query()->updateOrCreate(
                            [
                                'tenant_id' => PaymentCatalogAccess::currentTenantId(),
                                'method_id' => $record->id,
                            ],
                            ['is_visible' => $state]
                        );
                    })
                    ->disabled(fn ($record) => ! $record->statuspayment)
                    ->tooltip(fn ($record) => ! $record->statuspayment ? 'Metode dinonaktifkan oleh master admin.' : 'Toggle tampilan metode ini di storefront Anda.'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'bank' => 'Bank Transfer',
                        'e-walet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'virtual-account' => 'Virtual Account',
                        'convenience-store' => 'Convenience Store',
                        'SALDO' => 'Saldo',
                    ]),
                    
                SelectFilter::make('payment')
                    ->label('Gateway')
                    ->options([
                        'tripay' => 'Tripay',
                        'tokopay' => 'Tokopay',
                        'paydisini' => 'Paydisini',
                        'manual' => 'Manual',
                        'duitku' => 'Duitku',
                    ]),
                    
                SelectFilter::make('statuspayment')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(PaymentCatalogAccess::isMaster()),
            ])
            ->toolbarActions(PaymentCatalogAccess::isMaster() ? [
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ] : [])
            ->defaultSort('created_at', 'desc');
    }
}
