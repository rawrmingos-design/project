<?php

namespace App\Filament\Admin\Resources\Methods\Pages;

use App\Filament\Admin\Resources\Methods\MethodResource;
use App\Models\Method;
use App\Models\PaymentDisplayCategory;
use App\Services\MediaAssetAssignmentService;
use App\Services\OptimizedImageService;
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

        // Sync tipe from the selected display category's code (primary approach).
        // If no category is selected, attempt auto-assignment by matching tipe to canonical category.
        if (! empty($data['payment_display_category_id'])) {
            $category = PaymentDisplayCategory::find((int) $data['payment_display_category_id']);

            if ($category && filled($category->code)) {
                $data['tipe'] = $category->code;
                $this->autoAssignedCategory = $category->label;
            }
        } elseif (! empty($data['tipe'])) {
            // No category selected — try to auto-assign by tipe code match.
            $normalizedTipe = Method::normalizeTipe($data['tipe']);
            $category = PaymentDisplayCategory::canonical()
                ->where('code', $normalizedTipe)
                ->first();

            if (! $category) {
                $category = PaymentDisplayCategory::canonical()
                    ->where('label', $this->mapTipeToLabel($normalizedTipe))
                    ->first();
            }

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
                ->title('Kategori ditetapkan')
                ->body("Metode pembayaran ini masuk ke kategori \"{$this->autoAssignedCategory}\".")
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

    private function mapTipeToLabel(string $normalizedTipe): ?string
    {
        return match ($normalizedTipe) {
            'saldo'             => 'SALDO',
            'qris'              => 'QRIS',
            'bank'              => 'Bank Transfer',
            'e-walet'           => 'E-Wallet',
            'virtual-account'   => 'Virtual Account',
            'convenience-store' => 'Convenience Store',
            default             => null,
        };
    }
}
