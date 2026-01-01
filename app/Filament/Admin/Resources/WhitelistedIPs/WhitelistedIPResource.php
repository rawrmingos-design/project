<?php

namespace App\Filament\Admin\Resources\WhitelistedIPs;

use App\Filament\Admin\Resources\WhitelistedIPs\Pages\CreateWhitelistedIP;
use App\Filament\Admin\Resources\WhitelistedIPs\Pages\EditWhitelistedIP;
use App\Filament\Admin\Resources\WhitelistedIPs\Pages\ListWhitelistedIPs;
use App\Filament\Admin\Resources\WhitelistedIPs\Schemas\WhitelistedIPForm;
use App\Filament\Admin\Resources\WhitelistedIPs\Tables\WhitelistedIPsTable;
use App\Models\WhitelistedIP;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WhitelistedIPResource extends Resource
{
    protected static ?string $model = WhitelistedIP::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';
    
    protected static UnitEnum|string|null $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Whitelisted IPs';
    
    protected static ?string $modelLabel = 'Whitelisted IP';
    
    protected static ?string $pluralModelLabel = 'Whitelisted IPs';

    protected static ?string $recordTitleAttribute = 'ip_address';

    public static function form(Schema $schema): Schema
    {
        return WhitelistedIPForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WhitelistedIPsTable::configure($table);
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
            'index' => ListWhitelistedIPs::route('/'),
            'create' => CreateWhitelistedIP::route('/create'),
        ];
    }
}
