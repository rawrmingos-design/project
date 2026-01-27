<?php

namespace App\Filament\Admin\Resources\WithdrawalResource\Pages;

use App\Filament\Admin\Resources\WithdrawalResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageWithdrawals extends ManageRecords
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
