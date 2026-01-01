<?php

namespace App\Filament\Admin\Resources\Vouchers;

use App\Filament\Admin\Resources\Vouchers\Pages\CreateVoucher;
use App\Filament\Admin\Resources\Vouchers\Pages\EditVoucher;
use App\Filament\Admin\Resources\Vouchers\Pages\ListVouchers;
use App\Filament\Admin\Resources\Vouchers\Schemas\VoucherForm;
use App\Filament\Admin\Resources\Vouchers\Tables\VouchersTable;
use App\Models\Voucher;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';
    
    protected static UnitEnum|string|null $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Vouchers';
    
    protected static ?string $modelLabel = 'Voucher';
    
    protected static ?string $pluralModelLabel = 'Vouchers';

    protected static ?string $recordTitleAttribute = 'kode';

    public static function form(Schema $schema): Schema
    {
        return VoucherForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VouchersTable::configure($table);
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
            'index' => ListVouchers::route('/'),
            'create' => CreateVoucher::route('/create'),
            'edit' => EditVoucher::route('/{record}/edit'),
        ];
    }
}
