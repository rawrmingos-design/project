<?php

namespace App\Filament\Admin\Resources\Rewards\Pages;

use App\Filament\Admin\Resources\Rewards\RewardResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRewards extends ListRecords
{
    protected static string $resource = RewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
