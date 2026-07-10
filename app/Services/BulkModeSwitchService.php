<?php

namespace App\Services;

use App\Models\InboundSourcePolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BulkModeSwitchService
{
    /**
     * Identify policies with zero active entries from the given collection.
     *
     * @param  Collection<int, InboundSourcePolicy>  $policies
     * @return Collection<int, InboundSourcePolicy>  Policies that have no active entries
     */
    public function findEmptyPolicies(Collection $policies): Collection
    {
        if ($policies->isEmpty()) {
            return collect();
        }

        $policyIds = $policies->pluck('id')->all();

        $policiesWithCounts = InboundSourcePolicy::query()
            ->whereIn('id', $policyIds)
            ->withCount(['entries as active_entries_count' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return $policiesWithCounts->filter(
            fn (InboundSourcePolicy $policy) => $policy->active_entries_count === 0
        )->values();
    }

    /**
     * Execute the bulk mode switch.
     *
     * @param  Collection<int, InboundSourcePolicy>  $policies  Policies to update
     * @param  string  $targetMode  Target mode (disabled|log_only|enforce)
     * @param  string  $operatorIdentity  Authenticated user identifier
     * @param  Collection<int, InboundSourcePolicy>|null  $emptyPolicies  Pre-identified empty policies (for logging)
     * @return int  Number of policies updated
     */
    public function execute(
        Collection $policies,
        string $targetMode,
        string $operatorIdentity,
        ?Collection $emptyPolicies = null,
    ): int {
        $targetMode = InboundSourcePolicy::normalizeMode($targetMode);

        // Filter out policies already in the target mode
        $policiesToUpdate = $policies->filter(
            fn (InboundSourcePolicy $policy) => $policy->mode !== $targetMode
        );

        if ($policiesToUpdate->isEmpty()) {
            return 0;
        }

        $previousModes = [];

        foreach ($policiesToUpdate as $policy) {
            $previousModes[] = $policy->mode;
            $policy->mode = $targetMode;
            $policy->save();

            try {
                $policy->flushCache();
            } catch (\Throwable $e) {
                Log::warning('Cache flush failed during bulk mode switch', [
                    'policy_id' => $policy->id,
                    'source_domain' => $policy->source_domain,
                    'source_name' => $policy->source_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $updatedCount = $policiesToUpdate->count();

        $logContext = [
            'operator' => $operatorIdentity,
            'count' => $updatedCount,
            'previous_modes' => array_unique($previousModes),
            'target_mode' => $targetMode,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($emptyPolicies !== null && $emptyPolicies->isNotEmpty()) {
            $logContext['empty_policies'] = $emptyPolicies->map(
                fn (InboundSourcePolicy $policy) => "{$policy->source_domain}:{$policy->source_name}"
            )->all();
        }

        Log::info('Bulk mode switch executed', $logContext);

        return $updatedCount;
    }
}
