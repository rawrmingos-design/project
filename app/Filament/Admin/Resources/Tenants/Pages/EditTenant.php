<?php

namespace App\Filament\Admin\Resources\Tenants\Pages;

use App\Filament\Admin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Tenancy\TenantDomainService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verify_domain')
                ->label('Verify Domain')
                ->visible(fn (): bool => in_array(
                    $this->record->custom_domain_status,
                    [Tenant::DOMAIN_STATUS_PENDING, Tenant::DOMAIN_STATUS_FAILED],
                ))
                ->action(function (): void {
                    $service = app(TenantDomainService::class);
                    $result = $service->verifyDomain($this->record);

                    $this->record->refresh();

                    if ($result) {
                        Notification::make()
                            ->title('Domain verification passed')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Domain verification failed')
                            ->body($this->record->custom_domain_last_error)
                            ->danger()
                            ->send();
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
