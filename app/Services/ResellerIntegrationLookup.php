<?php

namespace App\Services;

use App\Models\ResellerIntegration;
use App\Models\User;

class ResellerIntegrationLookup
{
    public function findOwnedActive(User $user, string $integrationCode, string $mode): ?ResellerIntegration
    {
        return ResellerIntegration::query()
            ->where('integration_code', trim($integrationCode))
            ->where('user_id', $user->getKey())
            ->where('mode', trim($mode))
            ->where('is_active', true)
            ->first();
    }
}
