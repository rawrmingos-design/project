<?php

namespace App\Filament\Admin\Resources\PaymentDisplayCategories\Pages;

use App\Filament\Admin\Resources\PaymentDisplayCategories\PaymentDisplayCategoryResource;
use App\Support\PaymentCatalogAccess;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentDisplayCategories extends ListRecords
{
    protected static string $resource = PaymentDisplayCategoryResource::class;

    protected function getHeaderActions(): array
    {
        if (! PaymentCatalogAccess::isMaster()) {
            return [];
        }

        return [
            CreateAction::make(),
        ];
    }
}
