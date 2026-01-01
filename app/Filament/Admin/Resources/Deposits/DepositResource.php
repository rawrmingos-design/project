<?php

namespace App\Filament\Admin\Resources\Deposits;

use App\Filament\Admin\Resources\Deposits\Pages\CreateDeposit;
use App\Filament\Admin\Resources\Deposits\Pages\EditDeposit;
use App\Filament\Admin\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Admin\Resources\Deposits\Schemas\DepositForm;
use App\Filament\Admin\Resources\Deposits\Tables\DepositsTable;
use App\Models\Deposit;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    
    protected static UnitEnum|string|null $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Deposits';
    
    protected static ?string $modelLabel = 'Deposit';
    
    protected static ?string $pluralModelLabel = 'Deposits';

    protected static ?string $recordTitleAttribute = 'order_id';

    public static function form(Schema $schema): Schema
    {
        return DepositForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DepositsTable::configure($table);
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
            'index' => ListDeposits::route('/'),
            'create' => CreateDeposit::route('/create'),
            'edit' => EditDeposit::route('/{record}/edit'),
        ];
    }
}
