<?php

namespace App\Filament\Admin\Resources\PaymentDisplayCategories;

use App\Filament\Admin\Resources\PaymentDisplayCategories\Pages\CreatePaymentDisplayCategory;
use App\Filament\Admin\Resources\PaymentDisplayCategories\Pages\EditPaymentDisplayCategory;
use App\Filament\Admin\Resources\PaymentDisplayCategories\Pages\ListPaymentDisplayCategories;
use App\Filament\Admin\Resources\PaymentDisplayCategories\Schemas\PaymentDisplayCategoryForm;
use App\Filament\Admin\Resources\PaymentDisplayCategories\Tables\PaymentDisplayCategoriesTable;
use App\Models\PaymentDisplayCategory;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class PaymentDisplayCategoryResource extends Resource
{
    protected static ?string $model = PaymentDisplayCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static string|UnitEnum|null $navigationGroup = 'Metode Pembayaran';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?string $navigationLabel = 'Display Categories';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes()->whereNull('tenant_id');
    }

    public static function canCreate(): bool
    {
        return \App\Support\PaymentCatalogAccess::isMaster();
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Support\PaymentCatalogAccess::isMaster();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return \App\Support\PaymentCatalogAccess::isMaster();
    }

    public static function form(Schema $schema): Schema
    {
        return PaymentDisplayCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentDisplayCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentDisplayCategories::route('/'),
            'create' => CreatePaymentDisplayCategory::route('/create'),
            'edit' => EditPaymentDisplayCategory::route('/{record}/edit'),
        ];
    }
}
