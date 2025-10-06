<?php

namespace App\Filament\Admin\Resources\Layanans;

use App\Filament\Admin\Resources\Layanans\Pages\CreateLayanan;
use App\Filament\Admin\Resources\Layanans\Pages\EditLayanan;
use App\Filament\Admin\Resources\Layanans\Pages\ListLayanans;
use App\Filament\Admin\Resources\Layanans\Schemas\LayananForm;
use App\Filament\Admin\Resources\Layanans\Tables\LayanansTable;
use App\Models\Layanan;
use BackedEnum; 
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LayananResource extends Resource
{
    protected static ?string $model = Layanan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $recordTitleAttribute = 'layanan';
    
    protected static UnitEnum | string | null  $navigationGroup = 'Produk dan Layanan';
    
    protected static ?string $navigationLabel = 'Layanan';
    
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return LayananForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LayanansTable::configure($table);
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
            'index' => ListLayanans::route('/'),
            'create' => CreateLayanan::route('/create'),
            'edit' => EditLayanan::route('/{record}/edit'),
        ];
    }
}
