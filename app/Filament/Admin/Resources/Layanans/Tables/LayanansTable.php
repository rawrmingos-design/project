<?php

namespace App\Filament\Admin\Resources\Layanans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Kategori;

class LayanansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                    
                TextColumn::make('layanan')
                    ->label('Nama Layanan')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                TextColumn::make('kategori.nama')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('provider')
                    ->label('Provider')
                    ->badge()
                    ->colors([
                        'primary' => 'digiflazz',
                        'success' => 'apigames',
                        'warning' => 'vip',
                        'info' => 'manual',
                    ]),
                    
                TextColumn::make('harga')
                    ->label('Harga Normal')
                    ->money('IDR')
                    ->sortable(),
                    
                TextColumn::make('harga_member')
                    ->label('Harga Member')
                    ->money('IDR')
                    ->sortable(),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'maintenance',
                    ]),
                    
                BooleanColumn::make('is_flash_sale')
                    ->label('Flash Sale')
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-x-circle'),
                    
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('kategori_id')
                    ->label('Kategori')
                    ->options(Kategori::pluck('nama', 'id'))
                    ->searchable(),
                    
                SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        'digiflazz' => 'Digiflazz',
                        'apigames' => 'API Games',
                        'vip' => 'VIP Reseller',
                        'manual' => 'Manual',
                    ]),
                    
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'maintenance' => 'Maintenance',
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
