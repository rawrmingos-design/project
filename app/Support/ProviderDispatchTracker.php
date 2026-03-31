<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class ProviderDispatchTracker
{
    private const PREFIX = 'provider-dispatch-state:';

    public static function markQueued(int|string $pembelianId, int $ttlSeconds = 120): void
    {
        Cache::put(self::cacheKey($pembelianId), [
            'state' => 'queued',
            'updated_at' => now()->toDateTimeString(),
        ], now()->addSeconds($ttlSeconds));
    }

    public static function markProcessing(int|string $pembelianId, int $ttlSeconds = 180): void
    {
        Cache::put(self::cacheKey($pembelianId), [
            'state' => 'processing',
            'updated_at' => now()->toDateTimeString(),
        ], now()->addSeconds($ttlSeconds));
    }

    public static function clear(int|string $pembelianId): void
    {
        Cache::forget(self::cacheKey($pembelianId));
    }

    public static function getState(int|string $pembelianId): ?array
    {
        $value = Cache::get(self::cacheKey($pembelianId));

        return is_array($value) ? $value : null;
    }

    public static function isActive(int|string $pembelianId): bool
    {
        return self::getState($pembelianId) !== null;
    }

    public static function label(int|string $pembelianId): string
    {
        $state = self::getState($pembelianId)['state'] ?? null;

        return match ($state) {
            'queued' => 'Queued',
            'processing' => 'Processing',
            default => 'Idle',
        };
    }

    public static function badgeColor(int|string $pembelianId): string
    {
        $state = self::getState($pembelianId)['state'] ?? null;

        return match ($state) {
            'queued' => 'warning',
            'processing' => 'info',
            default => 'gray',
        };
    }

    private static function cacheKey(int|string $pembelianId): string
    {
        return self::PREFIX . (string) $pembelianId;
    }
}

