<?php

namespace App\Filament\Admin\Resources\RewardRedemptions;

use App\Filament\Admin\Resources\RewardRedemptions\Pages\EditRewardRedemption;
use App\Filament\Admin\Resources\RewardRedemptions\Pages\ListRewardRedemptions;
use App\Filament\Admin\Resources\RewardRedemptions\Schemas\RewardRedemptionForm;
use App\Filament\Admin\Resources\RewardRedemptions\Tables\RewardRedemptionsTable;
use App\Models\RewardRedemption;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class RewardRedemptionResource extends Resource
{
    protected static ?string $model = RewardRedemption::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static UnitEnum|string|null $navigationGroup = 'Hadiah';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Penukaran Hadiah';

    protected static ?string $modelLabel = 'Penukaran';

    protected static ?string $pluralModelLabel = 'Penukaran Hadiah';

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return RewardRedemptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RewardRedemptionsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', RewardRedemption::STATUS_PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRewardRedemptions::route('/'),
            'edit'  => EditRewardRedemption::route('/{record}/edit'),
        ];
    }
}
