<?php

namespace App\Filament\Admin\Resources\PaymentDisplayCategories\Pages;

use App\Filament\Admin\Resources\PaymentDisplayCategories\PaymentDisplayCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentDisplayCategories extends ListRecords
{
    protected static string $resource = PaymentDisplayCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
