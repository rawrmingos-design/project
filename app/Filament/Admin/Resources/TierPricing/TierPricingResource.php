<?php

namespace App\Filament\Admin\Resources\TierPricing;

use App\Filament\Admin\Resources\TierPricing\Pages\CreateTierPricing;
use App\Filament\Admin\Resources\TierPricing\Pages\EditTierPricing;
use App\Filament\Admin\Resources\TierPricing\Pages\ListTierPricing;
use App\Filament\Admin\Resources\TierPricing\Schemas\TierPricingForm;
use App\Filament\Admin\Resources\TierPricing\Tables\TierPricingTable;
use App\Models\TierPricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Menu admin untuk mengatur paket tenant.
 *
 * Inilah inti "platform bisa dijual": pemilik platform mengubah markup dan harga
 * paket dari sini, dan seluruh katalog tenant ikut menyesuaikan tanpa deploy.
 */
class TierPricingResource extends Resource
{
    protected static ?string $model = TierPricing::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Kemitraan';

    protected static ?string $navigationLabel = 'Tier Pricing';

    protected static ?string $modelLabel = 'Paket Tenant';

    protected static ?string $pluralModelLabel = 'Paket Tenant';

    protected static ?string $recordTitleAttribute = 'label';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return TierPricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TierPricingTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTierPricing::route('/'),
            'create' => CreateTierPricing::route('/create'),
            'edit' => EditTierPricing::route('/{record}/edit'),
        ];
    }
}
