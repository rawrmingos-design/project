<?php

namespace App\Filament\Admin\Resources\Artikels;

use App\Filament\Admin\Resources\Artikels\Pages;
use App\Filament\Admin\Resources\Artikels\Schemas\ArtikelForm;
use App\Filament\Admin\Resources\Artikels\Tables\ArtikelsTable;
use App\Models\Artikel;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class ArtikelResource extends Resource
{
    protected static ?string $model = Artikel::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    
    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Produk'; // Or 'Konten' if preferred, but sticking to existing groups
    
    protected static ?string $navigationLabel = 'Artikel & Berita';

    public static function form(Schema $schema): Schema
    {
        return ArtikelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArtikelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArtikels::route('/'),
            'create' => Pages\CreateArtikel::route('/create'),
            'edit' => Pages\EditArtikel::route('/{record}/edit'),
        ];
    }
}
