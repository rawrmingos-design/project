<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class PaymentStatus
{
    public const PAID = 'paid';
    public const PENDING = 'pending';
    public const EXPIRED = 'expired';
    public const CANCELLED = 'cancelled';

    public static function options(): array
    {
        return [
            self::PAID => 'Success',
            self::PENDING => 'Pending',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Gagal',
        ];
    }

    public static function switchOptions(): array
    {
        return [
            self::PENDING => 'Pending',
            self::CANCELLED => 'Gagal',
            self::EXPIRED => 'Expired',
            self::PAID => 'Sukses',
        ];
    }

    public static function label(?string $status): string
    {
        return match (self::code($status)) {
            self::PAID => 'Success',
            self::EXPIRED => 'Expired',
            self::CANCELLED => 'Gagal',
            default => 'Pending',
        };
    }

    public static function code(?string $status): string
    {
        return match (strtolower(trim((string) $status))) {
            'lunas', 'paid', 'success', 'sukses' => self::PAID,
            'expired', 'kedaluwarsa' => self::EXPIRED,
            'batal', 'cancelled', 'canceled', 'refunded', 'refund', 'failed', 'gagal' => self::CANCELLED,
            default => self::PENDING,
        };
    }

    public static function badgeColor(?string $status): string
    {
        return match (self::code($status)) {
            self::PAID => 'success',
            self::EXPIRED, self::CANCELLED => 'danger',
            default => 'warning',
        };
    }

    public static function icon(?string $status): string
    {
        return match (self::code($status)) {
            self::PAID => 'heroicon-o-check-circle',
            self::EXPIRED, self::CANCELLED => 'heroicon-o-x-circle',
            default => 'heroicon-o-clock',
        };
    }

    public static function rawValues(string $status): array
    {
        return match ($status) {
            self::PAID => ['Lunas', 'Paid', 'PAID', 'Success', 'success', 'Sukses', 'sukses'],
            self::PENDING => ['Belum Lunas', 'belum lunas', 'Unpaid', 'UNPAID', 'unpaid', 'Pending', 'PENDING', 'pending'],
            self::EXPIRED => ['Expired', 'expired'],
            self::CANCELLED => ['Batal', 'batal', 'Cancelled', 'cancelled', 'Canceled', 'canceled', 'Refunded', 'refunded', 'Failed', 'failed', 'Gagal', 'gagal'],
            default => [],
        };
    }

    public static function applyPembelianQuery(Builder $query, array $statuses): Builder
    {
        $statuses = array_values(array_filter($statuses));

        if ($statuses === []) {
            return $query;
        }

        $rawStatuses = collect($statuses)
            ->flatMap(static fn (string $status): array => self::rawValues($status))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $includeMissingPayments = in_array(self::PENDING, $statuses, true);

        if ($rawStatuses === [] && ! $includeMissingPayments) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($rawStatuses, $includeMissingPayments): void {
            if ($rawStatuses !== []) {
                $query->whereHas('pembayaran', function (Builder $paymentQuery) use ($rawStatuses): void {
                    $paymentQuery->whereIn('status', $rawStatuses);
                });
            }

            if ($includeMissingPayments) {
                if ($rawStatuses === []) {
                    $query->whereDoesntHave('pembayaran');
                } else {
                    $query->orWhereDoesntHave('pembayaran');
                }
            }
        });
    }
}
