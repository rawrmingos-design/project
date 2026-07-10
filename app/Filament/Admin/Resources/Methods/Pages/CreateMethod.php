<?php

namespace App\Filament\Admin\Resources\Methods\Pages;

use App\Filament\Admin\Resources\Methods\MethodResource;
use App\Models\Method;
use App\Services\MediaAssetAssignmentService;
use App\Services\OptimizedImageService;
use App\Services\PaymentDisplayCategoryService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateMethod extends CreateRecord
{
    protected static string $resource = MethodResource::class;

    protected ?int $selectedMediaAssetId = null;

    protected ?string $autoAssignedCategory = null;

    protected bool $noMatchingCategory = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedMediaAssetId = ($data['images_input_mode'] ?? 'upload') === 'library' && isset($data['images_media_asset_id'])
            ? (int) $data['images_media_asset_id']
            : null;

        if ($this->selectedMediaAssetId) {
            $path = app(MediaAssetAssignmentService::class)->getRelativePathFromAsset($this->selectedMediaAssetId);

            if ($path) {
                $data['images'] = $path;
            }
        }

        unset($data['images_media_asset_id'], $data['images_input_mode']);

        // Auto-assign category when none is explicitly selected
        if (empty($data['payment_display_category_id']) && ! empty($data['tipe'])) {
            $normalizedTipe = Method::normalizeTipe($data['tipe']);
            $service = app(PaymentDisplayCategoryService::class);
            $category = $service->mapTipeToCategory($normalizedTipe);

            if ($category) {
                $data['payment_display_category_id'] = $category->id;
                $this->autoAssignedCategory = $category->label;
            } else {
                $this->noMatchingCategory = true;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->applySelectedMediaAsset();
        $this->optimizeRecordImage();
        $this->notifyCategoryAssignment();
    }

    private function applySelectedMediaAsset(): void
    {
        if (! $this->selectedMediaAssetId) {
            return;
        }

        $record = $this->getRecord();

        if (! $record) {
            return;
        }

        $path = app(MediaAssetAssignmentService::class)->getRelativePathFromAsset($this->selectedMediaAssetId);

        if (! $path) {
            return;
        }

        $record->forceFill([
            'images' => $path,
        ])->saveQuietly();
    }

    private function optimizeRecordImage(): void
    {
        $record = $this->getRecord();

        if (! $record || ! $record->images) {
            return;
        }

        app(OptimizedImageService::class)->ensureVariants($record->images, 'payment_logo');
    }

    private function notifyCategoryAssignment(): void
    {
        if ($this->autoAssignedCategory) {
            Notification::make()
                ->title('Kategori otomatis ditetapkan')
                ->body("Metode pembayaran ini otomatis masuk ke kategori \"{$this->autoAssignedCategory}\" berdasarkan tipe-nya.")
                ->success()
                ->send();
        } elseif ($this->noMatchingCategory) {
            Notification::make()
                ->title('Kategori perlu ditetapkan manual')
                ->body('Tidak ditemukan kategori yang cocok dengan tipe metode ini. Silakan tetapkan kategori tampilan secara manual agar metode ditampilkan di halaman order.')
                ->warning()
                ->send();
        }
    }
}
