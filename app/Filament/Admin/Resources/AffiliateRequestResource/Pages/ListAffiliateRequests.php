<?php

namespace App\Filament\Admin\Resources\AffiliateRequestResource\Pages;

use App\Filament\Admin\Resources\AffiliateRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateRequests extends ListRecords
{
    protected static string $resource = AffiliateRequestResource::class;

    protected function getActions(): array
    {
        return [
            // No create action needed
        ];
    }
}
