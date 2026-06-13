<?php

namespace App\Services;

use App\Models\ResellerIntegration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResellerProvisioningService
{
    /**
     * Provision reseller integrations and return generated API keys.
     *
     * @return array{live_key: string|null, sandbox_key: string|null}
     */
    public function provision(User $user): array
    {
        $liveKey = null;
        $sandboxKey = null;

        DB::transaction(function () use ($user, &$liveKey, &$sandboxKey): void {
            $target = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->first();

            if (! $target) {
                return;
            }

            if ($target->role === 'Member') {
                $target->role = 'Gold';
                $target->save();
            }

            $liveKey = $this->firstOrCreateIntegration($target, 'live');
            $sandboxKey = $this->firstOrCreateIntegration($target, 'sandbox');
        });

        return [
            'live_key' => $liveKey,
            'sandbox_key' => $sandboxKey,
        ];
    }

    /**
     * Create or reactivate integration and return raw API key if newly generated.
     *
     * @return string|null Raw API key if newly created, null if already exists
     */
    private function firstOrCreateIntegration(User $user, string $mode): ?string
    {
        $existing = ResellerIntegration::query()
            ->where('user_id', $user->id)
            ->where('integration_type', 'provider')
            ->where('mode', $mode)
            ->orderBy('id')
            ->first();

        if ($existing) {
            if (! $existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }

            // Integration already exists, no new key generated
            return null;
        }

        // Generate new API key
        $apiKey = $this->generateApiKey($mode);

        ResellerIntegration::query()->create([
            'user_id' => $user->id,
            'integration_type' => 'provider',
            'credential_source' => 'global',
            'integration_code' => $this->generateUniqueIntegrationCode($user, $mode),
            'mode' => $mode,
            'api_key' => $apiKey, // This will be hashed by mutator
            'is_active' => true,
            'health_status' => null,
            'last_health_checked_at' => null,
            'notes' => 'Auto provisioned from reseller application approval.',
            'metadata' => [
                'source' => 'reseller_application',
                'auto_provisioned' => true,
                'provisioned_at' => now()->toIso8601String(),
            ],
        ]);

        // Return raw key before it's hashed
        return $apiKey;
    }

    private function generateApiKey(string $mode): string
    {
        $prefix = $mode === 'sandbox' ? 'rsbx_' : 'rliv_';

        return $prefix . Str::random(40);
    }

    private function generateUniqueIntegrationCode(User $user, string $mode): string
    {
        $base = Str::upper(Str::slug($user->username ?: ('user-' . $user->id), ''));
        $base = $base !== '' ? $base : ('USER' . $user->id);
        $modeTag = $mode === 'sandbox' ? 'SBX' : 'LIV';

        do {
            $candidate = sprintf('%s-%s-%s', $base, $modeTag, Str::upper(Str::random(6)));
        } while (ResellerIntegration::query()
            ->where('integration_type', 'provider')
            ->where('mode', $mode)
            ->where('integration_code', $candidate)
            ->exists());

        return $candidate;
    }
}
