<?php

namespace App\Http\Middleware;

use App\Models\InboundSourceEntry;
use App\Models\InboundSourcePolicy;
use App\Support\IpAddressMatcher;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InboundSourceWhitelist
{
    public function handle(
        Request $request,
        Closure $next,
        ?string $sourceDomain = null,
        ?string $sourceName = null,
        ?string $fallbackMode = null,
    ) {
        if (blank($sourceDomain) || blank($sourceName)) {
            Log::warning('Inbound whitelist middleware missing source metadata.', [
                'route_uri' => $request->route()?->uri(),
                'method' => $request->getMethod(),
            ]);

            return $next($request);
        }

        $resolvedSourceDomain = InboundSourcePolicy::normalizeKeyPart($sourceDomain);
        $resolvedSourceName = $this->resolveSourceName($request, $sourceName);
        $resolvedIp = $request->ip();
        $normalizedIp = IpAddressMatcher::normalize($resolvedIp);
        $route = $request->route();

        $context = [
            'route_uri' => $route?->uri(),
            'route_name' => $route?->getName(),
            'method' => $request->getMethod(),
            'source_domain' => $resolvedSourceDomain,
            'source_name' => $resolvedSourceName,
            'resolved_client_ip' => $resolvedIp,
            'normalized_client_ip' => $normalizedIp,
        ];

        try {
            $policy = InboundSourcePolicy::resolveCached($resolvedSourceDomain, $resolvedSourceName);
            $mode = InboundSourcePolicy::normalizeMode($policy?->mode ?? $fallbackMode);

            if ($mode === 'disabled') {
                $this->logDecision('info', 'allow', 'disabled', $context + ['mode' => $mode]);

                return $next($request);
            }

            if (! $policy || ! $policy->is_active) {
                return $this->handleNoPolicy($request, $next, $mode, $context, $policy ? 'policy_inactive' : 'no_policy');
            }

            $matchedEntry = $this->findMatchingEntry($policy->entries->all(), $normalizedIp);

            if ($matchedEntry !== null) {
                $this->logDecision('info', 'allow', 'matched', $context + [
                    'mode' => $mode,
                    'matched_entry_id' => $matchedEntry->id,
                    'matched_entry_value' => $matchedEntry->value,
                ]);

                return $next($request);
            }

            if ($mode === 'log_only') {
                $this->logDecision('warning', 'log_only_no_match', 'no_entry_match', $context + ['mode' => $mode]);

                return $next($request);
            }

            $this->logDecision('warning', 'deny', 'no_entry_match', $context + ['mode' => $mode]);

            return $this->deny();
        } catch (\Throwable $throwable) {
            $mode = InboundSourcePolicy::normalizeMode($fallbackMode);

            Log::error('Inbound whitelist evaluation failed.', $context + [
                'mode' => $mode,
                'reason' => 'policy_lookup_failed',
                'exception' => $throwable->getMessage(),
            ]);

            if ($mode === 'enforce') {
                return $this->deny();
            }

            return $next($request);
        }
    }

    private function resolveSourceName(Request $request, string $sourceName): string
    {
        $sourceName = trim($sourceName);

        if (str_starts_with($sourceName, '@')) {
            return InboundSourcePolicy::normalizeKeyPart((string) $request->route(ltrim($sourceName, '@'), ''));
        }

        return InboundSourcePolicy::normalizeKeyPart($sourceName);
    }

    /**
     * @param  array<int, InboundSourceEntry>  $entries
     */
    private function findMatchingEntry(array $entries, ?string $normalizedIp): ?InboundSourceEntry
    {
        foreach ($entries as $entry) {
            if (! $entry->is_active) {
                continue;
            }

            if (IpAddressMatcher::matches($normalizedIp, (string) $entry->value)) {
                return $entry;
            }
        }

        return null;
    }

    private function handleNoPolicy(Request $request, Closure $next, string $mode, array $context, string $reason)
    {
        if ($mode === 'enforce') {
            $this->logDecision('warning', 'deny', $reason, $context + ['mode' => $mode]);

            return $this->deny();
        }

        $this->logDecision('warning', 'log_only_no_match', $reason, $context + ['mode' => $mode]);

        return $next($request);
    }

    private function deny(): JsonResponse
    {
        return response()->json(
            config('inbound_whitelist.deny_body', ['message' => 'Forbidden']),
            (int) config('inbound_whitelist.deny_status', 403),
        );
    }

    private function logDecision(string $level, string $decision, string $reason, array $context): void
    {
        Log::log($level, 'Inbound whitelist decision.', $context + [
            'decision' => $decision,
            'reason' => $reason,
        ]);
    }
}
