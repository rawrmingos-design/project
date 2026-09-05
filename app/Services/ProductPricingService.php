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
        $memberPercent = $this->resolveExistingMarginPercent(
            storedPercent: $product->profit_member ?? null,
            currentSelling: $product->harga_member ?? null,
            currentModal: $currentModal,
            fallbackPercent: $defaults['member'],
        );
        $platinumPercent = $this->resolveExistingMarginPercent(
            storedPercent: $product->profit_platinum ?? null,
            currentSelling: $product->harga_platinum ?? null,
            currentModal: $currentModal,
            fallbackPercent: $defaults['platinum'],
        );
        $goldPercent = $this->resolveExistingMarginPercent(
            storedPercent: $product->profit_gold ?? null,
            currentSelling: $product->harga_gold ?? null,
            currentModal: $currentModal,
            fallbackPercent: $defaults['gold'],
        );

        $this->applyDirectTierPrices(
            $product,
            $modal,
            $modal + $this->calculatePercentMargin($modal, $memberPercent),
            $modal + $this->calculatePercentMargin($modal, $platinumPercent),
            $modal + $this->calculatePercentMargin($modal, $goldPercent),
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

    public function applyTierProfitPercentages(Model $product, array $percentages): void
    {
        $requested = [
            'member' => $percentages['member'] ?? null,
            'platinum' => $percentages['platinum'] ?? null,
            'gold' => $percentages['gold'] ?? null,
        ];

        $resolved = [];
        foreach ($requested as $tier => $value) {
            if ($value === null || $value === '') {
                $resolved[$tier] = (float) ($product->{'profit_' . $tier} ?? 0);
                continue;
            }

            if (! is_numeric($value) || (float) $value < 0 || (float) $value > 100) {
                throw new \InvalidArgumentException("Profit {$tier} harus berada di antara 0 dan 100 persen.");
            }

            $resolved[$tier] = (float) $value;
        }

        $modal = $this->normalizeAmount($product->harga ?? 0);
        $this->applyDirectTierPrices(
            $product,
            $modal,
            $modal + $this->calculatePercentMargin($modal, $resolved['member']),
            $modal + $this->calculatePercentMargin($modal, $resolved['platinum']),
            $modal + $this->calculatePercentMargin($modal, $resolved['gold']),
        );
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

    private function resolveExistingMarginPercent(
        int|float|null $storedPercent,
        int|float|null $currentSelling,
        int $currentModal,
        float $fallbackPercent
    ): float
    {
        if ($storedPercent !== null && is_numeric($storedPercent) && (float) $storedPercent >= 0) {
            return (float) $storedPercent;
        }

        $currentSelling = $this->normalizeAmount($currentSelling ?? 0);

        if ($currentModal > 0) {
            $existingPercent = $this->calculateMarginPercent($currentModal, $currentSelling);

            if ($existingPercent >= 0) {
                return (float) $existingPercent;
            }
        }

        return $fallbackPercent;
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
