<?php

namespace App\Filament\Admin\Resources\MediaAssets\Pages;

use App\Filament\Admin\Resources\MediaAssets\MediaAssetResource;
use App\Services\MediaAssetDeletionService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMediaAsset extends EditRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deletePermanently')
                ->label('Delete Permanently')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus file permanen?')
                ->modalDescription(function (): string {
                    $references = app(MediaAssetDeletionService::class)->references($this->record);
                    $usage = collect($references)->pluck('label')->implode("\n- ");

                    return $usage !== ''
                        ? "PERINGATAN: file ini masih dipakai oleh:\n- {$usage}\n\nSetelah dikonfirmasi, referensi tersebut akan dikosongkan, lalu file dan variants dihapus. Action ini tidak bisa di-undo."
                        : 'File ini tidak sedang dipakai oleh kategori, produk, banner, atau konfigurasi yang terdeteksi. Setelah dikonfirmasi, file dan variants dihapus permanen. Action ini tidak bisa di-undo.';
                })
                ->modalSubmitActionLabel('Ya, hapus permanen')
                ->action(function (MediaAssetDeletionService $deletionService): void {
                    $result = $deletionService->delete($this->record);

                    Notification::make()
                        ->title('File berhasil dihapus permanen')
                        ->body(sprintf(
                            'File deleted: %s, file skipped: %s, variants deleted: %d',
                            $result['file_deleted'] ? 'yes' : 'no',
                            $result['file_skipped'] ? 'yes' : 'no',
                            count($result['variants_deleted'] ?? []),
                        ))
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
