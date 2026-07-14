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

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';
    protected static string|UnitEnum|null $navigationGroup = 'Metode Pembayaran';

    protected static ?string $recordTitleAttribute = 'name';
    
    protected static ?string $navigationLabel = 'Payment Methods';
    
    protected static ?int $navigationSort = 1;

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

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes();

        if (\Illuminate\Support\Facades\Schema::hasColumn('methods', 'tenant_id')) {
            $query->whereNull('tenant_id');
        }

        return $query;
    }

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
