<?php

namespace App\Filament\Admin\Resources\ResellerCallbackProfiles\Pages;

use App\Filament\Admin\Resources\ResellerCallbackProfiles\ResellerCallbackProfileResource;
use App\Models\ResellerIntegration;
use App\Support\ResellerCallbackUrlValidator;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditResellerCallbackProfile extends EditRecord
{
    protected static string $resource = ResellerCallbackProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $integration = ResellerIntegration::query()->find($this->data['reseller_integration_id'] ?? $this->record->reseller_integration_id);
        $mode = $integration?->mode ?? 'live';
        $reason = ResellerCallbackUrlValidator::failureReason((string) ($this->data['callback_url'] ?? ''), $mode);

        if ($reason !== null) {
            throw ValidationException::withMessages([
                'data.callback_url' => $reason,
            ]);
        }
    }
}
