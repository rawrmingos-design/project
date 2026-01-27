<?php

namespace App\Filament\Admin\Resources\SettingWebResource\Pages;

use App\Filament\Admin\Resources\SettingWebResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSettingWeb extends EditRecord
{
    protected static string $resource = SettingWebResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(), // Usually we don't want to delete settings
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
