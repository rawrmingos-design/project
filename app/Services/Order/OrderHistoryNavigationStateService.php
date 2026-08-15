<?php

namespace App\Services\Order;

use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OrderHistoryNavigationStateService
{
    private const HANDLE_LENGTH = 16;

    private const TTL_MINUTES = 15;

    /**
     * @return non-empty-string
     */
    public function store(User $user, string $source, ?string $cursor): string
    {
        $scope = $this->scope($user, $source);

        do {
            $handle = Str::random(self::HANDLE_LENGTH);
            $key = $this->cacheKey($handle);
        } while (Cache::has($key));

        Cache::put($key, [
            'scope' => hash_hmac('sha256', $scope, (string) config('app.key')),
            'cursor' => $cursor,
        ], now()->addMinutes(self::TTL_MINUTES));

        return $handle;
    }

    /**
     * @return array{found: bool, cursor: string|null}
     */
    public function resolve(string $handle, User $user, string $source): array
    {
        if (preg_match('/^[A-Za-z0-9]{16}$/', $handle) !== 1) {
            return ['found' => false, 'cursor' => null];
        }

        $state = Cache::get($this->cacheKey($handle));
        if (
            ! is_array($state)
            || ! is_string($state['scope'] ?? null)
            || ! array_key_exists('cursor', $state)
            || (! is_null($state['cursor']) && ! is_string($state['cursor']))
        ) {
            return ['found' => false, 'cursor' => null];
        }

        $expectedScope = hash_hmac(
            'sha256',
            $this->scope($user, $source),
            (string) config('app.key'),
        );

        if (! hash_equals($expectedScope, $state['scope'])) {
            return ['found' => false, 'cursor' => null];
        }

        return [
            'found' => true,
            'cursor' => $state['cursor'],
        ];
    }

    private function cacheKey(string $handle): string
    {
        return 'bot:order-history-navigation:' . hash_hmac(
            'sha256',
            $handle,
            (string) config('app.key'),
        );
    }

    private function scope(User $user, string $source): string
    {
        if (! in_array($source, ['whatsapp_gateway', 'telegram_gateway'], true)) {
            throw new \InvalidArgumentException('Unsupported order history navigation source.');
        }

        $tenantId = app(TenantContext::class)->id();

        return implode('|', [
            'order-history-navigation',
            $tenantId === null ? 'landlord' : 'tenant:' . $tenantId,
            (string) $user->getKey(),
            $source,
        ]);
    }
}
