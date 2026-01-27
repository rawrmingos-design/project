<?php

namespace App\Filament\Admin\Resources\Produks;

use App\Filament\Admin\Resources\Produks\Pages\CreateProduk;
use App\Filament\Admin\Resources\Produks\Pages\EditProduk;
use App\Filament\Admin\Resources\Produks\Pages\ListProduks;
use App\Filament\Admin\Resources\Produks\Schemas\ProdukForm;
use App\Filament\Admin\Resources\Produks\Tables\ProduksTable;
use App\Models\Produk;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Produk';

    protected static ?string $recordTitleAttribute = 'layanan';
    
    protected static ?string $navigationLabel = 'Service';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $modelLabel = 'Service';
    
    protected static ?string $pluralModelLabel = 'Service';

    public static function form(Schema $schema): Schema
    {
        return ProdukForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProduksTable::configure($table);
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
            'index' => ListProduks::route('/'),
            'create' => CreateProduk::route('/create'),
            'edit' => EditProduk::route('/{record}/edit'),
        ];
    }
}
