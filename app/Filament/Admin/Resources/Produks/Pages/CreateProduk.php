<?php

namespace App\Filament\Admin\Resources\Produks\Pages;

use App\Filament\Admin\Resources\Produks\ProdukResource;
use App\Models\PaketLayanan;
use App\Services\MediaAssetAssignmentService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduk extends CreateRecord
{
    protected static string $resource = ProdukResource::class;

    protected ?int $selectedProductLogoMediaAssetId = null;

    protected string $productLogoInputMode = 'upload';

    protected array $selectedPaketIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->productLogoInputMode = (string) ($data['product_logo_input_mode'] ?? 'upload');
        $this->selectedPaketIds = collect($data['paket'] ?? [])
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
        $this->selectedProductLogoMediaAssetId = ($data['product_logo_input_mode'] ?? 'upload') === 'library'
            && filled($data['product_logo_media_asset_id'] ?? null)
                ? (int) $data['product_logo_media_asset_id']
                : null;

        // UI-only fields: must never be persisted to `layanans`.
        unset(
            $data['product_logo_media_asset_id'],
            $data['product_logo_input_mode'],
            $data['digiflazz_category_filter'],
            $data['digiflazz_brand_filter'],
            $data['digiflazz_product'],
        );

        return $this->syncDerivedProfitFields($data);
    }

    protected function afterCreate(): void
    {
        $this->syncPivotProductLogo();
    }

    private function syncDerivedProfitFields(array $data): array
    {
        $modal = (int) ($data['harga'] ?? 0);

        $data['profit_member'] = (float) ($data['profit_member'] ?? 0);
        $data['profit_platinum'] = (float) ($data['profit_platinum'] ?? 0);
        $data['profit_gold'] = (float) ($data['profit_gold'] ?? 0);

        $data['harga_member'] = $this->calculateSellingPrice($modal, $data['profit_member']);
        $data['harga_platinum'] = $this->calculateSellingPrice($modal, $data['profit_platinum']);
        $data['harga_gold'] = $this->calculateSellingPrice($modal, $data['profit_gold']);

        return $data;
    }

    private function calculateSellingPrice(int $modal, float $profitPercent): int
    {
        if ($modal <= 0) {
            return 0;
        }

        return (int) round($modal + ($modal * ($profitPercent / 100)));
    }

    private function syncPivotProductLogo(): void
    {
        $record = $this->getRecord()?->refresh();
        $currentPivotPath = $record
            ? PaketLayanan::query()->where('layanan_id', $record->id)->value('product_logo')
            : null;
        $logoPath = null;

        if ($this->productLogoInputMode === 'library') {
            $logoPath = $this->selectedProductLogoMediaAssetId
                ? app(MediaAssetAssignmentService::class)->getRelativePathFromAsset($this->selectedProductLogoMediaAssetId)
                : $currentPivotPath;
        }

        if ($record && $this->productLogoInputMode === 'upload' && empty($logoPath) && method_exists($record, 'getFirstMedia')) {
            $media = $record->getFirstMedia('product_logo');
            $logoPath = $media ? '/' . ltrim($media->getPathRelativeToRoot(), '/') : null;
        }

        if ($record && empty($logoPath) && ! empty($record->product_logo)) {
            $logoPath = $record->product_logo;
        }

        if (empty($logoPath)) {
            $logoPath = $currentPivotPath;
        }

        if (!$record || empty($logoPath)) {
            return;
        }

        $paketIds = $this->selectedPaketIds;

        if ($paketIds === []) {
            $paketIds = $record->paket()->pluck('pakets.id')->map(fn ($id): int => (int) $id)->all();
        }

        if ($paketIds !== []) {
            $record->paket()->syncWithoutDetaching(
                collect($paketIds)
                    ->mapWithKeys(fn (int $paketId): array => [
                        $paketId => ['product_logo' => $logoPath],
                    ])
                    ->all()
            );

            return;
        }

        PaketLayanan::where('layanan_id', $record->id)->update([
            'product_logo' => $logoPath,
            'updated_at' => now(),
        ]);
    }
}
