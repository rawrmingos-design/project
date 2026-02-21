<?php

namespace App\Filament\Admin\Resources\AffiliateHistoryResource\Pages;

use App\Filament\Admin\Resources\AffiliateHistoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateHistories extends ListRecords
{
    protected static string $resource = AffiliateHistoryResource::class;

    protected function getActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
