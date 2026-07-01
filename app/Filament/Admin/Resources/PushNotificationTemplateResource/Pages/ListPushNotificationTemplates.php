<?php

namespace App\Filament\Admin\Resources\PushNotificationTemplateResource\Pages;

use App\Filament\Admin\Resources\PushNotificationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPushNotificationTemplates extends ListRecords
{
    protected static string $resource = PushNotificationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
