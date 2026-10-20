<?php

namespace App\Filament\Admin\Resources\TierPricing\Pages;

use App\Filament\Admin\Resources\TierPricing\TierPricingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTierPricing extends ListRecords
{
    protected static string $resource = TierPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
