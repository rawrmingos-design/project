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
                    ->disk(config('uploads.disk', 'assets'))
                    ->getStateUsing(function ($record) {
                        $path = (string) ($record->path ?? '');

                        if ($path === '') {
                            return null;
                        }

                        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                            $path = (string) (parse_url($path, PHP_URL_PATH) ?? $path);
                        }

                        return ltrim($path, '/');
                    })
                    ->size(60)
                    ->imageWidth(400)
                    ->imageHeight(200)
                    ->square()
                    ->defaultImageUrl(fn () => null),
                    
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

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
                    
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
            ->defaultSort('urutan', 'asc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
