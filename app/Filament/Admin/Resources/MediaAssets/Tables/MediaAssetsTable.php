<?php

namespace App\Filament\Admin\Resources\MediaAssets\Tables;

use App\Models\MediaAsset;
use App\Support\MediaAssetPicker;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MediaAssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('file_url')
                    ->label('Preview')
                    ->size(56)
                    ->square(),

                IconColumn::make('valid_file')
                    ->label('Status')
                    ->getStateUsing(fn (MediaAsset $record): bool => filled($record->file_url))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('folder')
                    ->label('Folder')
                    ->badge()
                    ->placeholder('-')
                    ->sortable(),

                TextColumn::make('alt_text')
                    ->label('Alt Text')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('path')
                    ->label('Path')
                    ->limit(45)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('validity_label')
                    ->label('Keterangan')
                    ->getStateUsing(fn (MediaAsset $record): string => filled($record->file_url) ? 'Valid' : 'Invalid')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Valid' ? 'success' : 'danger')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('folder')
                    ->label('Folder')
                    ->options([
                        'produk' => 'Produk',
                        'kategori' => 'Kategori',
                        'banner' => 'Banner',
                        'artikel' => 'Artikel',
                        'logo' => 'Logo',
                        'lainnya' => 'Lainnya',
                    ]),
                SelectFilter::make('file_validity')
                    ->label('Validitas File')
                    ->options([
                        'valid' => 'Valid only',
                        'invalid' => 'Invalid only',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        $validIds = MediaAssetPicker::getUsableAssetIds();

                        if ($value === 'valid') {
                            return empty($validIds)
                                ? $query->whereRaw('1 = 0')
                                : $query->whereIn('id', $validIds);
                        }

                        return empty($validIds)
                            ? $query
                            : $query->whereNotIn('id', $validIds);
                    }),
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
