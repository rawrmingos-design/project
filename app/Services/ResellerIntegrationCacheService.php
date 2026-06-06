<?php

namespace App\Services;

use App\Models\ResellerIntegration;
use Illuminate\Support\Facades\Cache;

class ResellerIntegrationCacheService
{
    private const CACHE_TTL_MINUTES = 60; // 1 hour
    private const LAST_USED_THROTTLE_MINUTES = 5; // Update last_used_at max once every 5 mins

    /**
     * Resolves the reseller integration from cache or database.
     */
    public function resolveByHash(string $hash, string $mode): ?ResellerIntegration
    {
        $cacheKey = $this->getCacheKey($hash, $mode);

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($hash, $mode) {
            return ResellerIntegration::where('api_key_hash', $hash)
                ->where('mode', $mode)
                ->where('is_active', true)
                ->with('user')
                ->first();
        });
    }

    /**
     * Defers the update of api_key_last_used_at to after the response is sent,
     * throttled to prevent excessive DB writes.
     */
    public function touchLastUsed(ResellerIntegration $integration): void
    {
        $lastUsed = $integration->api_key_last_used_at;

        if ($lastUsed === null || now()->diffInMinutes($lastUsed) >= self::LAST_USED_THROTTLE_MINUTES) {
            // Update the object in memory immediately so sequential checks in the same request pass
            $integration->api_key_last_used_at = now();
            
            app()->terminating(function () use ($integration) {
                // Use query builder to bypass Eloquent events (avoiding infinite cache clearing loop)
                ResellerIntegration::where('id', $integration->id)
                    ->update(['api_key_last_used_at' => now()]);
            });
        }
    }

    /**
     * Clears the cached integration record.
     */
    public function forgetByHash(string $hash, string $mode): void
    {
        Cache::forget($this->getCacheKey($hash, $mode));
    }

    private function getCacheKey(string $hash, string $mode): string
    {
        return sprintf('reseller_int:%s:%s', $mode, $hash);
    }
}
