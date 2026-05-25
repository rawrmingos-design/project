<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies\Pages;

use App\Filament\Admin\Resources\InboundSourcePolicies\InboundSourcePolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInboundSourcePolicies extends ListRecords
{
    protected static string $resource = InboundSourcePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
