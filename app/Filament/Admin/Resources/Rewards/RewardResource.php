<?php

namespace App\Filament\Admin\Resources\Rewards;

use App\Filament\Admin\Resources\Rewards\Pages\CreateReward;
use App\Filament\Admin\Resources\Rewards\Pages\EditReward;
use App\Filament\Admin\Resources\Rewards\Pages\ListRewards;
use App\Filament\Admin\Resources\Rewards\Schemas\RewardForm;
use App\Filament\Admin\Resources\Rewards\Tables\RewardsTable;
use App\Models\Reward;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RewardResource extends Resource
{
    protected static ?string $model = Reward::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static UnitEnum|string|null $navigationGroup = 'Hadiah';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Katalog Hadiah';

    protected static ?string $modelLabel = 'Hadiah';

    protected static ?string $pluralModelLabel = 'Katalog Hadiah';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RewardForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RewardsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRewards::route('/'),
            'create' => CreateReward::route('/create'),
            'edit'   => EditReward::route('/{record}/edit'),
        ];
    }
}
