<?php

namespace App\Filament\Admin\Resources\MediaAssets;

use App\Filament\Admin\Resources\MediaAssets\Pages\CreateMediaAsset;
use App\Filament\Admin\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Admin\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Filament\Admin\Resources\MediaAssets\Schemas\MediaAssetForm;
use App\Filament\Admin\Resources\MediaAssets\Tables\MediaAssetsTable;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'File Manager';

    protected static ?string $modelLabel = 'File';

    protected static ?string $pluralModelLabel = 'File Manager';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return MediaAssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaAssetsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaAssets::route('/'),
            'create' => CreateMediaAsset::route('/create'),
            'edit' => EditMediaAsset::route('/{record}/edit'),
        ];
    }
}
