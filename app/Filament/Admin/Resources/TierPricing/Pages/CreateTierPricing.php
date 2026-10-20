<?php

namespace App\Filament\Admin\Resources\TierPricing\Pages;

use App\Filament\Admin\Resources\TierPricing\TierPricingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTierPricing extends CreateRecord
{
    protected static string $resource = TierPricingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
