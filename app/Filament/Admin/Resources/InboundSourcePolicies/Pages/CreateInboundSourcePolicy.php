<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies\Pages;

use App\Filament\Admin\Resources\InboundSourcePolicies\InboundSourcePolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInboundSourcePolicy extends CreateRecord
{
    protected static string $resource = InboundSourcePolicyResource::class;
}
