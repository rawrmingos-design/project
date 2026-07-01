<?php

namespace App\Filament\Admin\Resources\Providers\Pages;

use App\Filament\Admin\Resources\Providers\ProviderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageProviders extends ManageRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
