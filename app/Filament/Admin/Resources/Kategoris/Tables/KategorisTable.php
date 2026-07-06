<?php

namespace App\Filament\Admin\Resources\Kategoris\Tables;

use App\Filament\Admin\Resources\Kategoris\KategoriResource;
use App\Models\Kategori;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class KategorisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y H:i:s')
                    ->description(fn ($record) => $record->updated_at?->format('M d, Y H:i:s'))
                    ->sortable(),

                TextColumn::make('id')
                    ->label('ID')
                    ->searchable(),

                TextColumn::make('nama')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable(),

                ImageColumn::make('thumbnail')
                    ->label('Thumbnail')
                    ->disk(config('uploads.disk', 'assets'))
                    ->visibility('public')
                    ->width(40)
                    ->height(40)
                    ->circular(),

                TextColumn::make('categoryType.name')
                    ->label('Category Sequence')
                    ->default('-'),

                TextColumn::make('region')
                    ->label('Region')
                    ->default('Indonesia'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Tipe')
                    ->options([
                        'game' => 'Game',
                        'voucher' => 'Voucher',
                        'pulsa' => 'Pulsa',
                        'data' => 'Data',
                    ]),
                    
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Kategori $record): string => KategoriResource::getUrl('edit', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
