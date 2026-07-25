<?php

namespace App\Filament\Admin\Resources\ResellerOrders;

use App\Filament\Admin\Resources\ResellerOrders\Pages\ListResellerOrders;
use App\Filament\Admin\Resources\ResellerOrders\Pages\ViewResellerOrder;
use App\Filament\Admin\Resources\ResellerOrders\Tables\ResellerOrdersTable;
use App\Models\Pembelian;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use UnitEnum;

class ResellerOrderResource extends Resource
{
    protected static ?string $model = Pembelian::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static UnitEnum|string|null $navigationGroup = 'Reseller Management';

    protected static ?string $recordTitleAttribute = 'order_id';

    protected static ?string $navigationLabel = 'Reseller Orders';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Reseller Order';

    protected static ?string $pluralModelLabel = 'Reseller Orders';

    // Disable create and edit - read-only views only
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return ResellerOrdersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select([
                'id', 'order_id', 'username', 'user_id', 'zone', 'nickname', 'layanan',
                'harga', 'profit', 'provider_order_id', 'status', 'traffic_source', 'voucher',
                'keterangan_sn', 'tipe_transaksi', 'email_pembeli', 'ip_address', 'used_points',
                'used_point_amount', 'created_at', 'updated_at', 'base_order_id', 'invoice_version',
                'display_order_id', 'active_layanan_id', 'active_provider_code', 'active_provider_sku',
                'active_attempt_token', 'active_attempt_reference', 'reset_status', 'reset_count',
                'reset_requested_by', 'reset_requested_at', 'reset_reason', 'reseller_integration_id',
                'is_sandbox', 'environment', 'refund_amount', 'refunded_at', 'tenant_commission_credited_at'
            ])
            ->whereNotNull('reseller_integration_id') // Only reseller API orders
            ->with([
                'pembayaran:id,order_id,status,no_pembeli,metode',
                'user:id,username,name,email,no_wa',
                'resellerIntegration:id,user_id,api_key_prefix',
                'resellerIntegration.user:id,username,name,email,no_wa',
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
            'index' => ListResellerOrders::route('/'),
            'view' => ViewResellerOrder::route('/{record}'),
        ];
    }
}
