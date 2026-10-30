<?php

namespace App\Filament\Admin\Resources\Rewards\Pages;

use App\Filament\Admin\Resources\Rewards\RewardResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReward extends EditRecord
{
    protected static string $resource = RewardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
