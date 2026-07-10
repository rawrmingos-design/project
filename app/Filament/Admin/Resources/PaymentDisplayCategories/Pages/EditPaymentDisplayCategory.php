<?php

namespace App\Filament\Admin\Resources\PaymentDisplayCategories\Pages;

use App\Filament\Admin\Resources\PaymentDisplayCategories\PaymentDisplayCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentDisplayCategory extends EditRecord
{
    protected static string $resource = PaymentDisplayCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
