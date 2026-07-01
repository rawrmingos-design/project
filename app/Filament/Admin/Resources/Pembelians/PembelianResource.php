<?php

namespace App\Filament\Admin\Resources\Pembelians;

use App\Filament\Admin\Resources\Pembelians\Pages\ListPembelians;
use App\Filament\Admin\Resources\Pembelians\Pages\ViewPembelian;
use App\Filament\Admin\Resources\Pembelians\Tables\PembeliansTable;
use App\Models\Pembelian;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class PembelianResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Transaksi';

    protected static ?string $recordTitleAttribute = 'order_id';

    protected static ?string $navigationLabel = 'Pembelian';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Pembelian';

    protected static ?string $pluralModelLabel = 'Pembelian';

    // Disable create and edit - read-only views only
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return PembeliansTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('reseller_integration_id') // Exclude reseller API orders - only show regular customer orders
            ->with([
                'pembayaran:id,order_id,status,no_pembeli,metode',
                'user:id,username,name,email,no_wa',
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
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
            'index' => ListPembelians::route('/'),
            'view' => ViewPembelian::route('/{record}'),
        ];
    }
}
