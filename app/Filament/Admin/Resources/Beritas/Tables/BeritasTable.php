<?php

namespace App\Filament\Admin\Resources\Beritas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class BeritasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')
                    ->label('Image')
                    ->disk('assets')
                    ->size(60)
                    ->imageWidth(400)
                    ->imageHeight(200)
                    ->square(),
                    
                TextColumn::make('tipe')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'banner' => 'primary',
                        'popup' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'banner' => 'heroicon-o-photo',
                        'popup' => 'heroicon-o-window',
                        default => null,
                    }),
                    
                TextColumn::make('deskripsi')
                    ->label('Description')
                    ->html()
                    ->limit(100)
                    ->wrap()
                    ->placeholder('No description')
                    ->toggleable(),
                    
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
                SelectFilter::make('tipe')
                    ->label('Type')
                    ->options([
                        'banner' => 'Banner',
                        'popup' => 'Popup',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
