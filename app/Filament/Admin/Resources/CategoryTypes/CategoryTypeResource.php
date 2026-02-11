<?php

namespace App\Filament\Admin\Resources\CategoryTypes;

use App\Filament\Admin\Resources\CategoryTypes\Pages\CreateCategoryType;
use App\Filament\Admin\Resources\CategoryTypes\Pages\EditCategoryType;
use App\Filament\Admin\Resources\CategoryTypes\Pages\ListCategoryTypes;
use App\Filament\Admin\Resources\CategoryTypes\Schemas\CategoryTypeForm;
use App\Filament\Admin\Resources\CategoryTypes\Tables\CategoryTypesTable;
use App\Models\CategoryType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CategoryTypeResource extends Resource
{
    protected static ?string $model = CategoryType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Produk';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CategoryTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoryTypesTable::configure($table);
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
            'index' => ListCategoryTypes::route('/'),
            'create' => CreateCategoryType::route('/create'),
            'edit' => EditCategoryType::route('/{record}/edit'),
        ];
    }
}
