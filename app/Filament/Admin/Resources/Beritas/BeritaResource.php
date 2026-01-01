<?php

namespace App\Filament\Admin\Resources\Beritas;

use App\Filament\Admin\Resources\Beritas\Pages\CreateBerita;
use App\Filament\Admin\Resources\Beritas\Pages\EditBerita;
use App\Filament\Admin\Resources\Beritas\Pages\ListBeritas;
use App\Filament\Admin\Resources\Beritas\Schemas\BeritaForm;
use App\Filament\Admin\Resources\Beritas\Tables\BeritasTable;
use App\Models\Berita;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BeritaResource extends Resource
{
    protected static ?string $model = Berita::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';
    
    protected static UnitEnum|string|null $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $navigationLabel = 'Banners & Popups';
    
    protected static ?string $modelLabel = 'Banner/Popup';
    
    protected static ?string $pluralModelLabel = 'Banners & Popups';

    protected static ?string $recordTitleAttribute = 'tipe';

    public static function form(Schema $schema): Schema
    {
        return BeritaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BeritasTable::configure($table);
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
            'index' => ListBeritas::route('/'),
            'create' => CreateBerita::route('/create'),
            'edit' => EditBerita::route('/{record}/edit'),
        ];
    }
}
