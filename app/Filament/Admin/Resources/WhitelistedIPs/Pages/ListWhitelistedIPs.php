<?php

namespace App\Filament\Admin\Resources\WhitelistedIPs\Pages;

use App\Filament\Admin\Resources\WhitelistedIPs\WhitelistedIPResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhitelistedIPs extends ListRecords
{
    protected static string $resource = WhitelistedIPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
