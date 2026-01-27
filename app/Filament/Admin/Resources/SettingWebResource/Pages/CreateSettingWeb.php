<?php

namespace App\Filament\Admin\Resources\SettingWebResource\Pages;

use App\Filament\Admin\Resources\SettingWebResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSettingWeb extends CreateRecord
{
    protected static string $resource = SettingWebResource::class;
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
