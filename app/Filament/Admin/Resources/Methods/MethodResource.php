<?php

namespace App\Filament\Admin\Resources\Methods;

use App\Filament\Admin\Resources\Methods\Pages\CreateMethod;
use App\Filament\Admin\Resources\Methods\Pages\EditMethod;
use App\Filament\Admin\Resources\Methods\Pages\ListMethods;
use App\Filament\Admin\Resources\Methods\Schemas\MethodForm;
use App\Filament\Admin\Resources\Methods\Tables\MethodsTable;
use App\Models\Method;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MethodResource extends Resource
{
    protected static ?string $model = Method::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';
    
    protected static UnitEnum | string | null  $navigationGroup = 'Configuration';
    
    protected static ?string $navigationLabel = 'Payment Methods';
    
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return MethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MethodsTable::configure($table);
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
            'index' => ListMethods::route('/'),
            'create' => CreateMethod::route('/create'),
            'edit' => EditMethod::route('/{record}/edit'),
        ];
    }
}
