<?php

namespace App\Filament\Admin\Resources\WhatsappTemplateResource\Pages;

use App\Filament\Admin\Resources\WhatsappTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappTemplate extends EditRecord
{
    protected static string $resource = WhatsappTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
