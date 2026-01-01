<?php

namespace App\Filament\Admin\Resources\WhitelistedIPs\Pages;

use App\Filament\Admin\Resources\WhitelistedIPs\WhitelistedIPResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhitelistedIP extends CreateRecord
{
    protected static string $resource = WhitelistedIPResource::class;
}
