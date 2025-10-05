<?php

namespace App\Filament\Admin\Resources\Methods\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;

class MethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('images')
                    ->label('Logo')
                    ->circular()
                    ->size(40),
                    
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
                        'success' => 'ewallet',
                        'warning' => 'qris',
                        'info' => 'virtual_account',
                        'secondary' => 'convenience_store',
                    ]),
                    
                BadgeColumn::make('payment')
                    ->label('Gateway')
                    ->colors([
                        'primary' => 'tripay',
                        'success' => 'tokopay',
                        'warning' => 'paydisini',
                        'secondary' => 'manual',
                    ]),
                    
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
                    
                BooleanColumn::make('statuspayment')
                    ->label('Status')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
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
                        'ewallet' => 'E-Wallet',
                        'qris' => 'QRIS',
                        'virtual_account' => 'Virtual Account',
                        'convenience_store' => 'Convenience Store',
                    ]),
                    
                SelectFilter::make('payment')
                    ->label('Gateway')
                    ->options([
                        'tripay' => 'Tripay',
                        'tokopay' => 'Tokopay',
                        'paydisini' => 'Paydisini',
                        'manual' => 'Manual',
                    ]),
                    
                SelectFilter::make('statuspayment')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
