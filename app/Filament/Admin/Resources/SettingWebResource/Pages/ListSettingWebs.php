<?php

namespace App\Filament\Admin\Resources\SettingWebResource\Pages;

use App\Filament\Admin\Resources\SettingWebResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\SettingWeb;

class ListSettingWebs extends ListRecords
{
    protected static string $resource = SettingWebResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Only allow create if no settings exist
            Actions\CreateAction::make()
                ->visible(fn () => SettingWeb::count() === 0),
        ];
    }
}
