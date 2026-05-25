<?php

namespace App\Filament\Admin\Resources\ResellerIntegrations\Pages;

use App\Filament\Admin\Resources\ResellerIntegrations\ResellerIntegrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResellerIntegration extends EditRecord
{
    protected static string $resource = ResellerIntegrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
