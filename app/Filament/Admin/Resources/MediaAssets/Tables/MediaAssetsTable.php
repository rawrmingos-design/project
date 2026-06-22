<?php

namespace App\Filament\Admin\Resources\MediaAssets\Tables;

use App\Models\MediaAsset;
use App\Services\MediaAssetDeletionService;
use App\Support\MediaAssetPicker;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
                IconColumn::make('is_image_file')
                    ->label('Tipe')
                    ->boolean()
                    ->trueIcon('heroicon-o-photo')
                    ->falseIcon('heroicon-o-document')
                    ->trueColor('info')
                    ->falseColor('gray'),

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

                TextColumn::make('file_extension')
                    ->label('Ext')
                    ->badge()
                    ->getStateUsing(fn (MediaAsset $record): string => strtoupper((string) ($record->file_extension ?: '-')))
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('path', $direction)),

                TextColumn::make('file_size_human')
                    ->label('Ukuran')
                    ->toggleable(),

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
                    ->copyable()
                    ->copyMessage('Path disalin')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('file_url')
                    ->label('URL')
                    ->limit(45)
                    ->copyable()
                    ->copyMessage('URL file disalin')
                    ->url(fn (MediaAsset $record): ?string => $record->file_url, shouldOpenInNewTab: true)
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
                        'seasonal' => 'Seasonal',
                        'dokumen' => 'Dokumen',
                        'xml' => 'XML',
                        'lainnya' => 'Lainnya',
                    ]),
                SelectFilter::make('file_type')
                    ->label('Jenis File')
                    ->options([
                        'image' => 'Image',
                        'xml' => 'XML',
                        'document' => 'Document',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $type = $data['value'] ?? null;

                        if (blank($type)) {
                            return $query;
                        }

                        return match ($type) {
                            'image' => $query->where(function (Builder $builder): void {
                                $builder
                                    ->whereHas('media', fn (Builder $mediaQuery) => $mediaQuery->where('mime_type', 'like', 'image/%'))
                                    ->orWhere('path', 'like', '%.jpg')
                                    ->orWhere('path', 'like', '%.jpeg')
                                    ->orWhere('path', 'like', '%.png')
                                    ->orWhere('path', 'like', '%.webp')
                                    ->orWhere('path', 'like', '%.gif')
                                    ->orWhere('path', 'like', '%.svg');
                            }),
                            'xml' => $query->where(function (Builder $builder): void {
                                $builder
                                    ->whereHas('media', fn (Builder $mediaQuery) => $mediaQuery->whereIn('mime_type', ['application/xml', 'text/xml']))
                                    ->orWhere('path', 'like', '%.xml');
                            }),
                            'document' => $query->where(function (Builder $builder): void {
                                $builder
                                    ->whereHas('media', fn (Builder $mediaQuery) => $mediaQuery->whereIn('mime_type', [
                                        'application/pdf',
                                        'text/plain',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/zip',
                                        'application/x-zip-compressed',
                                    ]))
                                    ->orWhere('path', 'like', '%.pdf')
                                    ->orWhere('path', 'like', '%.txt')
                                    ->orWhere('path', 'like', '%.doc')
                                    ->orWhere('path', 'like', '%.docx')
                                    ->orWhere('path', 'like', '%.xls')
                                    ->orWhere('path', 'like', '%.xlsx')
                                    ->orWhere('path', 'like', '%.zip');
                            }),
                            default => $query,
                        };
                    }),
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
                    BulkAction::make('deletePermanently')
                        ->label('Delete Permanently')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus file permanen?')
                        ->modalDescription('File Manager akan menghapus record database dan file fisik dari disk server jika file berada di folder asset yang dikelola. Optimized image variants juga ikut dihapus. Jika asset masih dipakai produk/kategori/banner, tampilan terkait bisa broken. Action ini tidak bisa di-undo.')
                        ->modalSubmitActionLabel('Ya, hapus permanen')
                        ->action(function ($records, MediaAssetDeletionService $deletionService): void {
                            $summary = [
                                'records' => 0,
                                'files_deleted' => 0,
                                'files_skipped' => 0,
                                'variants_deleted' => 0,
                            ];

                            foreach ($records as $record) {
                                $result = $deletionService->delete($record);

                                $summary['records']++;
                                $summary['files_deleted'] += $result['file_deleted'] ? 1 : 0;
                                $summary['files_skipped'] += $result['file_skipped'] ? 1 : 0;
                                $summary['variants_deleted'] += count($result['variants_deleted'] ?? []);
                            }

                            Notification::make()
                                ->title('File berhasil dihapus permanen')
                                ->body("Records: {$summary['records']}, files deleted: {$summary['files_deleted']}, files skipped: {$summary['files_skipped']}, variants deleted: {$summary['variants_deleted']}")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
