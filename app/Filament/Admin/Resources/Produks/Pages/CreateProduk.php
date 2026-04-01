<?php

namespace App\Filament\Admin\Resources\Produks\Pages;

use App\Filament\Admin\Resources\Produks\ProdukResource;
use App\Models\PaketLayanan;
use App\Services\MediaAssetAssignmentService;
use App\Services\ProductPricingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

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
            $data['bangjeff_product_code_filter'],
            $data['bangjeff_variant'],
        );

        $data = $this->normalizeAndValidateProviderPaths($data);

        return $this->syncDerivedProfitFields($data);
    }

    protected function afterCreate(): void
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

    private function normalizeAndValidateProviderPaths(array $data): array
    {
        $paths = collect($data['provider_paths'] ?? [])
            ->map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                $providerCode = strtolower(trim((string) ($row['provider_code'] ?? '')));
                $providerSku = trim((string) ($row['provider_sku'] ?? ''));

                $row['provider_code'] = $providerCode;
                $row['provider_sku'] = $providerSku;

                if (array_key_exists('priority', $row)) {
                    $row['priority'] = max(1, (int) ($row['priority'] ?? 1));
                }

                if (array_key_exists('modal_price', $row)) {
                    $row['modal_price'] = max(0, (float) ($row['modal_price'] ?? 0));
                }

                return $row;
            })
            ->values();

        $duplicateKeys = $paths
            ->filter(fn ($row): bool => is_array($row)
                && filled($row['provider_code'] ?? null)
                && filled($row['provider_sku'] ?? null))
            ->groupBy(fn ($row): string => ($row['provider_code'] ?? '') . '|' . ($row['provider_sku'] ?? ''))
            ->filter(fn ($group): bool => $group->count() > 1)
            ->keys()
            ->values();

        if ($duplicateKeys->isNotEmpty()) {
            throw ValidationException::withMessages([
                'data.provider_paths' => 'Provider path duplikat terdeteksi: ' . $duplicateKeys->implode(', '),
            ]);
        }

        $data['provider_paths'] = $paths->all();

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
