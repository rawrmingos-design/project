<?php

namespace App\Filament\Admin\Resources\MediaAssets\Pages;

use App\Filament\Admin\Resources\MediaAssets\MediaAssetResource;
use App\Services\MediaAssetFolderSyncService;
use App\Services\MediaAssetInvalidCleanupService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMediaAssets extends ListRecords
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cleanupInvalidAssets')
                ->label('Cleanup Invalid Assets')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->action(function (MediaAssetInvalidCleanupService $cleanupService): void {
                    $result = $cleanupService->cleanup(true);

                    Notification::make()
                        ->title('Cleanup invalid assets selesai')
                        ->body("Scanned: {$result['scanned']}, invalid: {$result['invalid']}, deleted: {$result['deleted']}")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
            Action::make('syncAssetFolders')
                ->label('Sync Asset Folders')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function (MediaAssetFolderSyncService $syncService): void {
                    $result = $syncService->sync();

                    Notification::make()
                        ->title('Sync asset folders selesai')
                        ->body("Created: {$result['created']}, skipped: {$result['skipped']}")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua'),
            'produk' => Tab::make('Produk')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'produk')),
            'kategori' => Tab::make('Kategori')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'kategori')),
            'banner' => Tab::make('Banner')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'banner')),
            'artikel' => Tab::make('Artikel')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'artikel')),
            'logo' => Tab::make('Logo')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'logo')),
            'seasonal' => Tab::make('Seasonal')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'seasonal')),
            'dokumen' => Tab::make('Dokumen')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'dokumen')),
            'xml' => Tab::make('XML')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'xml')),
            'lainnya' => Tab::make('Lainnya')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('folder', 'lainnya')),
        ];
    }
}
