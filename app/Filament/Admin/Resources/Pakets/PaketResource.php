<?php

namespace App\Filament\Admin\Resources\Pakets;

use App\Filament\Admin\Resources\Pakets\Pages;
use App\Filament\Admin\Resources\Pakets\Schemas\PaketForm;
use App\Filament\Admin\Resources\Pakets\Tables\PaketsTable;
use App\Models\Paket;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;


class PaketResource extends Resource
{
    protected static ?string $model = Paket::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube-transparent';
    
    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Produk';
    
    protected static ?string $navigationLabel = 'Paket Layanan';

    public static function form(Schema $schema): Schema
    {
        return PaketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaketsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\Pakets\RelationManagers\LayananRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPakets::route('/'),
            'create' => Pages\CreatePaket::route('/create'),
            'edit' => Pages\EditPaket::route('/{record}/edit'),
        ];
    }
}
