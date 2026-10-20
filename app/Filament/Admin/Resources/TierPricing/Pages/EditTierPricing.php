<?php

namespace App\Filament\Admin\Resources\TierPricing\Pages;

use App\Filament\Admin\Resources\TierPricing\TierPricingResource;
use Filament\Resources\Pages\EditRecord;

class EditTierPricing extends EditRecord
{
    protected static string $resource = TierPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
