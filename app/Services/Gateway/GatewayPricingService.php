<?php

namespace App\Services\Gateway;

use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\User;
use App\Models\Voucher;
use App\Services\CheckId\CheckIdResolver;
use App\Services\PaymentMethodCatalogService;
use Illuminate\Validation\ValidationException;

class GatewayPricingService
{
    public function quote(array $payload, ?User $user = null): array
    {
        $service = Layanan::query()
            ->whereKey((int) ($payload['service_id'] ?? $payload['service'] ?? 0))
            ->where('status', 'available')
            ->first();

        if (! $service) {
            throw ValidationException::withMessages([
                'service_id' => 'Layanan tidak ditemukan atau tidak tersedia.',
            ]);
        }

        $category = Kategori::query()->findOrFail($service->kategori_id);
        $method = app(PaymentMethodCatalogService::class)->findVisibleByCode((string) ($payload['payment_method'] ?? ''));

        if (! $method) {
            throw ValidationException::withMessages([
                'payment_method' => 'Metode pembayaran tidak valid atau tidak aktif.',
            ]);
        }

        $baseAmount = $this->resolveBaseAmount($service, $category, $user, $payload);
        $discount = $this->resolveVoucherDiscount($baseAmount, $payload);
        $amountAfterDiscount = max(0, $baseAmount - $discount);
        $feeAmount = $this->methodFee($amountAfterDiscount, $method);
        $totalAmount = max(1000, $amountAfterDiscount + $feeAmount);

        $limitMessage = $this->validateMethodLimit($totalAmount, $method);
        if ($limitMessage !== null) {
            throw ValidationException::withMessages([
                'payment_method' => $limitMessage,
            ]);
        }

        return [
            'ok' => true,
            'message' => 'Harga berhasil dihitung.',
            'data' => [
                'service_id' => $service->id,
                'service_name' => (string) $service->layanan,
                'category_code' => (string) $category->kode,
                'category_name' => (string) $category->nama,
                'requires_user_id' => (bool) $category->require_user_id,
                'requires_zone_id' => $this->requiresZoneId($category),
                'base_amount' => $baseAmount,
                'discount' => $discount,
                'amount_after_discount' => $amountAfterDiscount,
                'payment_fee' => $feeAmount,
                'total_amount' => $totalAmount,
                'payment_method' => [
                    'code' => (string) $method->code,
                    'name' => (string) $method->name,
                    'type' => (string) $method->payment,
                ],
                'flash_sale_applied' => $this->isFlashSaleActive($service),
            ],
        ];
    }

    private function requiresZoneId(Kategori $category): bool
    {
        return (bool) $category->server_id
            && ! app(CheckIdResolver::class)->isZoneless((string) $category->kode);
    }

    private function resolveBaseAmount(Layanan $service, Kategori $category, ?User $user, array $payload): int
    {
        $amount = match ($user?->role ?? 'Guest') {
            'Member' => $service->harga_member,
            'Platinum' => $service->harga_platinum,
            'Gold', 'Admin' => $service->harga_gold,
            default => $service->harga_member,
        };

        if ($this->isFlashSaleActive($service)) {
            $amount = $service->harga_flash_sale;
        }

        $orderType = (string) ($payload['ktg_tipe'] ?? $category->tipe ?? 'game');
        if (in_array($orderType, ['joki', 'jokigendong', 'vilogml'], true)) {
            $amount *= max(1, (int) ($payload['qty'] ?? 1));
        }

        return max(0, (int) round((float) $amount));
    }

    private function resolveVoucherDiscount(int $baseAmount, array $payload): int
    {
        $voucherCode = trim((string) ($payload['voucher'] ?? ''));
        if ($voucherCode === '') {
            return 0;
        }

        $voucher = Voucher::query()->where('kode', $voucherCode)->first();
        if (! $voucher || ! $voucher->isUsable()) {
            throw ValidationException::withMessages([
                'voucher' => 'Voucher tidak valid atau sudah kadaluarsa.',
            ]);
        }

        if ($voucher->mintrx && $baseAmount < $voucher->mintrx) {
            throw ValidationException::withMessages([
                'voucher' => 'Minimal transaksi untuk voucher ini adalah Rp ' . number_format((int) $voucher->mintrx, 0, ',', '.'),
            ]);
        }

        return min((int) round($baseAmount * ((float) $voucher->promo / 100)), (int) $voucher->max_potongan);
    }

    private function methodFee(int $amount, Method $method): int
    {
        return max(0, (int) round(((float) $method->fix_fee) + ($amount * ((float) $method->fee_percent / 100))));
    }

    private function validateMethodLimit(int $amount, Method $method): ?string
    {
        $min = (int) ($method->min_pembelian ?? 0);
        $max = (int) ($method->max_pembelian ?? 0);

        if ($min > 0 && $amount < $min) {
            return 'Minimal pembayaran untuk metode ini adalah Rp ' . number_format($min, 0, ',', '.');
        }

        if ($max > 0 && $amount > $max) {
            return 'Maksimal pembayaran untuk metode ini adalah Rp ' . number_format($max, 0, ',', '.');
        }

        return null;
    }

    private function isFlashSaleActive(Layanan $service): bool
    {
        return (bool) $service->is_flash_sale
            && $service->expired_flash_sale !== null
            && $service->expired_flash_sale->gte(now())
            && (int) $service->stock_flash_sale > 0;
    }
}
