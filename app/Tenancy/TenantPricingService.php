<?php

namespace App\Tenancy;

use App\Models\Layanan;
use App\Models\Tenant;

class TenantPricingService
{
    private const DEFAULT_MARKUP_PERCENT = 10;

    public function forLayanan(Layanan $layanan, ?Tenant $tenant = null, int $quantity = 1): array
    {
        $tenant ??= app(TenantContext::class)->get();
        $quantity = max(1, $quantity);
        $modal = max(0, (int) round((float) ($layanan->harga_gold ?? 0))) * $quantity;
        $markup = $this->calculateMarkup($modal, $tenant);
        $sellPrice = max($modal, $modal + $markup);

        return [
            'modal' => $modal,
            'markup' => $markup,
            'profit' => $markup,
            'sell_price' => $sellPrice,
        ];
    }

    public function applyToLayanan(Layanan $layanan, ?Tenant $tenant = null, int $quantity = 1): Layanan
    {
        $price = $this->forLayanan($layanan, $tenant, $quantity);

        $layanan->harga = $price['sell_price'];
        $layanan->modal_harga = $price['modal'];
        $layanan->profit = $price['profit'];
        $layanan->tenant_modal_harga = $price['modal'];
        $layanan->tenant_profit = $price['profit'];

        return $layanan;
    }

    private function calculateMarkup(int $modal, ?Tenant $tenant): int
    {
        $config = is_array($tenant?->margin_config) ? $tenant->margin_config : [];
        $type = strtolower(trim((string) ($config['markup_type'] ?? 'percent')));
        $value = (float) ($config['markup_value'] ?? self::DEFAULT_MARKUP_PERCENT);

        if ($value < 0) {
            $value = 0;
        }

        if ($type === 'fixed') {
            return (int) round($value);
        }

        return (int) round($modal * ($value / 100));
    }
}
