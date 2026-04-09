<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class GtmDataLayerBuilder
{
    public function buildCatalog(iterable $layanans, object $kategori): array
    {
        $catalog = [];

        foreach ($layanans as $layanan) {
            $item = $this->buildItem([
                'item_id' => $layanan->id ?? null,
                'item_name' => $layanan->layanan ?? $kategori->nama ?? 'Produk',
                'item_category' => $kategori->nama ?? 'Produk',
                'item_variant' => $kategori->tipe ?? null,
                'price' => $layanan->harga ?? 0,
                'quantity' => 1,
            ]);

            if (! empty($item['item_id'])) {
                $catalog[(string) $item['item_id']] = $item;
            }
        }

        return $catalog;
    }

    public function buildPaymentMethods(iterable $methods): array
    {
        $catalog = [];

        foreach ($methods as $method) {
            $code = trim((string) ($method->code ?? ''));

            if ($code === '') {
                continue;
            }

            $catalog[$code] = [
                'code' => $code,
                'name' => trim((string) ($method->name ?? $code)),
                'provider' => trim((string) ($method->payment ?? '')),
            ];
        }

        return $catalog;
    }

    public function buildItem(array $attributes): array
    {
        return array_filter([
            'item_id' => $this->sanitizeString($attributes['item_id'] ?? null),
            'item_name' => $this->sanitizeString($attributes['item_name'] ?? null) ?? 'Produk',
            'item_category' => $this->sanitizeString($attributes['item_category'] ?? null) ?? 'Produk',
            'item_variant' => $this->sanitizeString($attributes['item_variant'] ?? null),
            'price' => $this->normalizeMoney($attributes['price'] ?? 0),
            'quantity' => max(1, (int) ($attributes['quantity'] ?? 1)),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    public function buildViewItemPayload(array $item, string $currency = 'IDR'): array
    {
        return [
            'ecommerce' => [
                'currency' => $currency,
                'value' => $this->normalizeMoney($item['price'] ?? 0),
                'items' => [$item],
            ],
        ];
    }

    public function buildCheckoutPayload(array $item, string $paymentType, int|float $value, string $currency = 'IDR'): array
    {
        $eventItem = $item;
        $eventItem['price'] = $this->normalizeMoney($value);

        return [
            'payment_type' => $this->sanitizeString($paymentType) ?? 'Tidak Diketahui',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $this->normalizeMoney($value),
                'items' => [$eventItem],
            ],
        ];
    }

    public function buildInvoiceViewedPayload(
        string $transactionId,
        string $paymentType,
        string $paymentStatus,
        string $orderStatus,
        int|float $value,
        array $item,
        string $currency = 'IDR',
    ): array {
        return [
            'transaction_id' => $transactionId,
            'payment_type' => $this->sanitizeString($paymentType) ?? 'Tidak Diketahui',
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'value' => $this->normalizeMoney($value),
            'currency' => $currency,
            'items' => [$item],
        ];
    }

    public function buildAddPaymentInfoPayload(string $transactionId, string $paymentType, int|float $value, array $item, string $currency = 'IDR'): array
    {
        $eventItem = $item;
        $eventItem['price'] = $this->normalizeMoney($value);

        return [
            'transaction_id' => $transactionId,
            'payment_type' => $this->sanitizeString($paymentType) ?? 'Tidak Diketahui',
            'ecommerce' => [
                'currency' => $currency,
                'value' => $this->normalizeMoney($value),
                'payment_type' => $this->sanitizeString($paymentType) ?? 'Tidak Diketahui',
                'items' => [$eventItem],
            ],
        ];
    }

    public function buildPurchasePayload(string $transactionId, string $paymentType, int|float $value, array $item, string $currency = 'IDR'): array
    {
        $eventItem = $item;
        $eventItem['price'] = $this->normalizeMoney($value);

        return [
            'transaction_id' => $transactionId,
            'payment_type' => $this->sanitizeString($paymentType) ?? 'Tidak Diketahui',
            'ecommerce' => [
                'transaction_id' => $transactionId,
                'currency' => $currency,
                'value' => $this->normalizeMoney($value),
                'payment_type' => $this->sanitizeString($paymentType) ?? 'Tidak Diketahui',
                'items' => [$eventItem],
            ],
        ];
    }

    public function buildOperationalPayload(
        string $transactionId,
        string $paymentType,
        string $paymentStatus,
        string $orderStatus,
        int|float $value,
        array $item,
        string $currency = 'IDR',
    ): array {
        return [
            'transaction_id' => $transactionId,
            'payment_type' => $this->sanitizeString($paymentType) ?? 'Tidak Diketahui',
            'payment_status' => $paymentStatus,
            'order_status' => $orderStatus,
            'value' => $this->normalizeMoney($value),
            'currency' => $currency,
            'items' => [$item],
        ];
    }

    private function sanitizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = Str::of((string) $value)
            ->squish()
            ->limit(120, '');

        return $string->isEmpty() ? null : $string->value();
    }

    private function normalizeMoney(int|float|string|null $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        return (int) round((float) $value);
    }
}
