<?php

namespace App\Filament\Admin\Resources\ResellerCallbackProfiles\Pages;

use App\Filament\Admin\Resources\ResellerCallbackProfiles\ResellerCallbackProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditResellerCallbackProfile extends EditRecord
{
    protected static string $resource = ResellerCallbackProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
