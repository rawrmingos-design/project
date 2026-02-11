<?php

namespace App\Filament\Admin\Resources\WhatsappTemplateResource\Pages;

use App\Filament\Admin\Resources\WhatsappTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappTemplates extends ListRecords
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
