<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies\Pages;

use App\Filament\Admin\Resources\InboundSourcePolicies\InboundSourcePolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInboundSourcePolicy extends EditRecord
{
    protected static string $resource = InboundSourcePolicyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
