<?php

namespace App\Services;

use App\Models\SettingWeb;
use Illuminate\Database\Eloquent\Model;

class ProductPricingService
{
    public function applyDirectTierPrices(Model $product, int|float $modal, ?int $member = null, ?int $platinum = null, ?int $gold = null): void
    {
        $modal = $this->normalizeAmount($modal);
        $member = max($modal, $this->normalizeAmount($member ?? $modal));
        $platinum = max($modal, $this->normalizeAmount($platinum ?? $member));
        $gold = max($modal, $this->normalizeAmount($gold ?? $platinum));

        $product->harga = $modal;
        $product->harga_member = $member;
        $product->harga_platinum = $platinum;
        $product->harga_gold = $gold;

        $this->syncLegacyProfitColumns($product);
    }

    public function seedFromBaseCostWithDefaultMarkup(Model $product, int|float $modal): void
    {
        $modal = $this->normalizeAmount($modal);
        $defaults = $this->getDefaultTierPercentages();

        $this->applyDirectTierPrices(
            $product,
            $modal,
            $modal + $this->calculatePercentMargin($modal, $defaults['member']),
            $modal + $this->calculatePercentMargin($modal, $defaults['platinum']),
            $modal + $this->calculatePercentMargin($modal, $defaults['gold']),
        );
    }

    public function rebaseFromNewBaseCostKeepingMargins(Model $product, int|float $modal): void
    {
        $modal = $this->normalizeAmount($modal);
        $defaults = $this->getDefaultTierPercentages();
        $currentModal = $this->normalizeAmount($product->harga ?? 0);

        $memberMargin = $this->resolveExistingMargin(
            currentSelling: $product->harga_member ?? null,
            currentModal: $currentModal,
            newModal: $modal,
            fallbackPercent: $defaults['member'],
        );
        $platinumMargin = $this->resolveExistingMargin(
            currentSelling: $product->harga_platinum ?? null,
            currentModal: $currentModal,
            newModal: $modal,
            fallbackPercent: $defaults['platinum'],
        );
        $goldMargin = $this->resolveExistingMargin(
            currentSelling: $product->harga_gold ?? null,
            currentModal: $currentModal,
            newModal: $modal,
            fallbackPercent: $defaults['gold'],
        );

        $this->applyDirectTierPrices(
            $product,
            $modal,
            $modal + $memberMargin,
            $modal + $platinumMargin,
            $modal + $goldMargin,
        );
    }

    public function adjustSellingPricesByPercent(Model $product, int|float $percent): void
    {
        $percent = (float) $percent;
        $modal = $this->normalizeAmount($product->harga ?? 0);

        $member = max($modal, (int) round(($product->harga_member ?? $modal) * (1 + ($percent / 100))));
        $platinum = max($modal, (int) round(($product->harga_platinum ?? $member) * (1 + ($percent / 100))));
        $gold = max($modal, (int) round(($product->harga_gold ?? $platinum) * (1 + ($percent / 100))));

        $this->applyDirectTierPrices($product, $modal, $member, $platinum, $gold);
    }

    public function syncLegacyProfitColumns(Model $product): void
    {
        $modal = $this->normalizeAmount($product->harga ?? 0);

        $product->profit_member = $this->calculateMarginPercent($modal, $product->harga_member ?? $modal);
        $product->profit_platinum = $this->calculateMarginPercent($modal, $product->harga_platinum ?? $modal);
        $product->profit_gold = $this->calculateMarginPercent($modal, $product->harga_gold ?? $modal);
    }

    public function getDefaultTierPercentages(): array
    {
        $settings = SettingWeb::query()->first();

        return [
            'member' => (float) ($settings->profit_member ?? 0),
            'platinum' => (float) ($settings->profit_platinum ?? 0),
            'gold' => (float) ($settings->profit_gold ?? 0),
        ];
    }

    private function resolveExistingMargin(int|float|null $currentSelling, int $currentModal, int $newModal, float $fallbackPercent): int
    {
        $currentSelling = $this->normalizeAmount($currentSelling ?? 0);

        if ($currentModal > 0) {
            $existingMargin = $currentSelling - $currentModal;

            if ($existingMargin >= 0) {
                return $existingMargin;
            }
        }

        return $this->calculatePercentMargin($newModal, $fallbackPercent);
    }

    private function calculatePercentMargin(int $modal, float $percent): int
    {
        return (int) round($modal * ($percent / 100));
    }

    private function calculateMarginPercent(int $modal, int|float|null $sellingPrice): int
    {
        $sellingPrice = $this->normalizeAmount($sellingPrice ?? 0);

        if ($modal <= 0 || $sellingPrice <= $modal) {
            return 0;
        }

        return (int) round((($sellingPrice - $modal) / $modal) * 100);
    }

    private function normalizeAmount(int|float|null $amount): int
    {
        return max(0, (int) round((float) ($amount ?? 0)));
    }
}
