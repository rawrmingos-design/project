<?php

namespace App\Support;

final class PembelianStatus
{
    public const SUCCESS = 'success';
    public const PENDING = 'pending';
    public const PROCESSING = 'processing';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';
    public const UNKNOWN = 'unknown';

    private const STATUS_ALIASES = [
        self::SUCCESS => ['Success', 'Sukses'],
        self::PENDING => ['Pending'],
        self::PROCESSING => ['Processing', 'Proses', 'Process'],
        self::FAILED => ['Failed', 'Gagal', 'Error'],
        self::CANCELLED => ['Batal', 'Cancelled', 'Canceled'],
        self::REFUNDED => ['Refunded'],
    ];

    private const DISPLAY_LABELS = [
        self::SUCCESS => 'Success',
        self::PENDING => 'Pending',
        self::PROCESSING => 'Processing',
        self::FAILED => 'Failed',
        self::CANCELLED => 'Cancelled',
        self::REFUNDED => 'Refunded',
        self::UNKNOWN => 'Unknown',
    ];

    private const BADGE_COLORS = [
        self::SUCCESS => 'success',
        self::PENDING => 'warning',
        self::PROCESSING => 'info',
        self::FAILED => 'danger',
        self::CANCELLED => 'danger',
        self::REFUNDED => 'gray',
        self::UNKNOWN => 'secondary',
    ];

    private const ICONS = [
        self::SUCCESS => 'heroicon-o-check-circle',
        self::PENDING => 'heroicon-o-clock',
        self::PROCESSING => 'heroicon-o-arrow-path',
        self::FAILED => 'heroicon-o-x-circle',
        self::CANCELLED => 'heroicon-o-x-circle',
        self::REFUNDED => 'heroicon-o-arrow-uturn-left',
        self::UNKNOWN => 'heroicon-o-question-mark-circle',
    ];

    private const PREFERRED_DATABASE_LABELS = [
        self::SUCCESS => 'Sukses',
        self::PENDING => 'Pending',
        self::PROCESSING => 'Processing',
        self::FAILED => 'Gagal',
        self::CANCELLED => 'Batal',
        self::REFUNDED => 'Refunded',
        self::UNKNOWN => 'Pending',
    ];

    public static function normalize(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));

        if ($normalized === '') {
            return self::UNKNOWN;
        }

        foreach (self::STATUS_ALIASES as $canonical => $aliases) {
            foreach ($aliases as $alias) {
                if ($normalized === strtolower($alias)) {
                    return $canonical;
                }
            }
        }

        return self::UNKNOWN;
    }

    public static function aliasesFor(string $canonical): array
    {
        return self::STATUS_ALIASES[$canonical] ?? [];
    }

    public static function filterOptions(): array
    {
        return [
            self::SUCCESS => self::label(self::SUCCESS),
            self::PENDING => self::label(self::PENDING),
            self::PROCESSING => self::label(self::PROCESSING),
            self::FAILED => self::label(self::FAILED),
            self::CANCELLED => self::label(self::CANCELLED),
            self::REFUNDED => self::label(self::REFUNDED),
        ];
    }

    public static function label(?string $status): string
    {
        return self::DISPLAY_LABELS[self::normalize($status)] ?? self::DISPLAY_LABELS[self::UNKNOWN];
    }

    public static function badgeColor(?string $status): string
    {
        return self::BADGE_COLORS[self::normalize($status)] ?? self::BADGE_COLORS[self::UNKNOWN];
    }

    public static function icon(?string $status): string
    {
        return self::ICONS[self::normalize($status)] ?? self::ICONS[self::UNKNOWN];
    }

    public static function preferredDatabaseLabel(?string $status): string
    {
        return self::PREFERRED_DATABASE_LABELS[self::normalize($status)] ?? self::PREFERRED_DATABASE_LABELS[self::UNKNOWN];
    }

    public static function apiStatusCode(?string $status): string
    {
        return match (self::normalize($status)) {
            self::SUCCESS => 'Success',
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Canceled',
            self::REFUNDED => 'Refunded',
            default => 'Pending',
        };
    }

    public static function isFinal(?string $status): bool
    {
        return in_array(self::normalize($status), [self::SUCCESS, self::FAILED, self::CANCELLED, self::REFUNDED], true);
    }

    public static function shouldIgnoreTransition(?string $currentStatus, ?string $incomingStatus): bool
    {
        $current = self::normalize($currentStatus);
        $incoming = self::normalize($incomingStatus);

        if ($current === $incoming) {
            return false;
        }

        if ($current === self::SUCCESS && $incoming !== self::SUCCESS) {
            return true;
        }

        if (in_array($current, [self::FAILED, self::CANCELLED, self::REFUNDED], true)) {
            return true;
        }

        return false;
    }

    public static function manualStatusOptions(): array
    {
        return [
            'Sukses'     => 'Sukses',
            'Pending'    => 'Pending',
            'Processing' => 'Processing',
            'Proses'     => 'Proses',
            'Failed'     => 'Failed',
            'Gagal'      => 'Gagal',
            'Batal'      => 'Batal',
            'Refunded'   => 'Refunded',
        ];
    }

    /**
     * Phase 5 — Task 5.4
     * DB label sets for use in OrderLogController ?status= filter.
     * These match the PREFERRED_DATABASE_LABELS values stored in the DB.
     */
    public static function failedLabels(): array
    {
        return ['Gagal', 'Batal', 'Failed'];
    }

    public static function pendingLabels(): array
    {
        return ['Pending', 'Processing', 'Proses'];
    }

    public static function successLabels(): array
    {
        return ['Sukses', 'Success'];
    }
}
