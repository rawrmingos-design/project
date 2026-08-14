<?php

namespace App\Services\Whatsapp;

use App\Models\User;
use App\Support\WhatsappNumberNormalizer;
use App\Tenancy\TenantContext;

class WhatsappUserResolver
{
    public const STATUS_LINKED = 'linked';

    public const STATUS_UNREGISTERED = 'unregistered';

    public const STATUS_REGISTERED_UNVERIFIED = 'registered_unverified';

    public const STATUS_AMBIGUOUS = 'ambiguous';

    public const STATUS_TENANT_MISMATCH = 'tenant_mismatch';

    public const STATUS_UNAVAILABLE = 'unavailable';

    /**
     * @return array{status: string, number: ?string, user?: User}
     */
    public function resolve(?string $number): array
    {
        $normalizedNumber = WhatsappNumberNormalizer::normalize($number);

        if ($normalizedNumber === null) {
            return [
                'status' => self::STATUS_UNAVAILABLE,
                'number' => null,
            ];
        }

        $users = User::query()
            ->where('no_wa', $normalizedNumber)
            ->get();

        if ($users->count() > 1) {
            return [
                'status' => self::STATUS_AMBIGUOUS,
                'number' => $normalizedNumber,
            ];
        }

        if ($users->isEmpty()) {
            if ($this->hasTenantMismatch($normalizedNumber)) {
                return [
                    'status' => self::STATUS_TENANT_MISMATCH,
                    'number' => $normalizedNumber,
                ];
            }

            return [
                'status' => self::STATUS_UNREGISTERED,
                'number' => $normalizedNumber,
            ];
        }

        /** @var User $user */
        $user = $users->first();

        if ($user->whatsapp_verified_at === null) {
            return [
                'status' => self::STATUS_REGISTERED_UNVERIFIED,
                'number' => $normalizedNumber,
            ];
        }

        return [
            'status' => self::STATUS_LINKED,
            'number' => $normalizedNumber,
            'user' => $user,
        ];
    }

    private function hasTenantMismatch(string $number): bool
    {
        if (! app()->bound(TenantContext::class) || ! app(TenantContext::class)->has()) {
            return false;
        }

        return User::query()
            ->withoutGlobalScope('tenant')
            ->where('no_wa', $number)
            ->exists();
    }
}
