<?php

namespace App\Filament\Admin\Resources\Produks\Pages;

use App\Filament\Admin\Resources\Produks\ProdukResource;
use App\Models\PaketLayanan;
use App\Services\MediaAssetAssignmentService;
use App\Services\ProductPricingService;
use App\Support\MediaAssetPicker;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProduk extends EditRecord
{
    protected static string $resource = ProdukResource::class;

    protected ?int $selectedProductLogoMediaAssetId = null;

    protected string $productLogoInputMode = 'upload';

    protected array $selectedPaketIds = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            'product_logo_media_asset_id' => MediaAssetPicker::resolveCurrentMediaAssetId($this->getRecord(), 'product_logo'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        $this->syncPivotProductLogo();
    }

    private function syncDerivedProfitFields(array $data): array
    {
        $draft = new \App\Models\Produk();

        app(ProductPricingService::class)->applyDirectTierPrices(
            $draft,
            (int) ($data['harga'] ?? 0),
            (int) ($data['harga_member'] ?? 0),
            (int) ($data['harga_platinum'] ?? 0),
            (int) ($data['harga_gold'] ?? 0),
        );

        $data['harga'] = (int) $draft->harga;
        $data['harga_member'] = (int) $draft->harga_member;
        $data['harga_platinum'] = (int) $draft->harga_platinum;
        $data['harga_gold'] = (int) $draft->harga_gold;
        $data['profit_member'] = (float) $draft->profit_member;
        $data['profit_platinum'] = (float) $draft->profit_platinum;
        $data['profit_gold'] = (float) $draft->profit_gold;

        return $data;
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
