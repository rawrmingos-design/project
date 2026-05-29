<?php

namespace App\Filament\Admin\Resources\ResellerCallbackProfiles\Pages;

use App\Filament\Admin\Resources\ResellerCallbackProfiles\ResellerCallbackProfileResource;
use App\Models\ResellerIntegration;
use App\Support\ResellerCallbackUrlValidator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateResellerCallbackProfile extends CreateRecord
{
    protected static string $resource = ResellerCallbackProfileResource::class;

    protected function beforeCreate(): void
    {
        $integration = ResellerIntegration::query()->find($this->data['reseller_integration_id'] ?? null);
        $mode = $integration?->mode ?? 'live';
        $reason = ResellerCallbackUrlValidator::failureReason((string) ($this->data['callback_url'] ?? ''), $mode);

        if ($reason !== null) {
            throw ValidationException::withMessages([
                'data.callback_url' => $reason,
            ]);
        }
    }
}
