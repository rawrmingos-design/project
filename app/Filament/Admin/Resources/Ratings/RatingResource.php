<?php

namespace App\Filament\Admin\Resources\Ratings;

use App\Filament\Admin\Resources\Ratings\Pages;
use App\Filament\Admin\Resources\Ratings\Schemas\RatingForm;
use App\Filament\Admin\Resources\Ratings\Tables\RatingsTable;
use App\Models\Rating;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;

class RatingResource extends Resource
{
    protected static ?string $model = Rating::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-star';
    
    protected static UnitEnum|string|null $navigationGroup = 'Manajemen Produk';
    
    protected static ?string $navigationLabel = 'Ulasan Pembeli';

    public static function form(Schema $schema): Schema
    {
        return RatingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RatingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRatings::route('/'),
            'create' => Pages\CreateRating::route('/create'),
            'edit' => Pages\EditRating::route('/{record}/edit'),
        ];
    }
}
