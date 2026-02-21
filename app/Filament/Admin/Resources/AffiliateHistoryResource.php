<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AffiliateHistoryResource\Pages;
use App\Models\AffiliateHistory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use UnitEnum;
use BackedEnum;

class AffiliateHistoryResource extends Resource
{
    protected static ?string $model = AffiliateHistory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Laporan Affiliate';
    
    protected static ?string $pluralLabel = 'Laporan Affiliate';

    protected static UnitEnum|string|null $navigationGroup = 'Affiliate System';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('uplink_id')
                    ->relationship('uplink', 'username')
                    ->disabled()
                    ->label('Uplink User'),
                Select::make('downlink_id')
                    ->relationship('downlink', 'username')
                    ->disabled()
                    ->label('Downlink User'),
                TextInput::make('order_id')
                    ->disabled(),
                TextInput::make('amount')
                    ->disabled()
                    ->label('Komisi'),
                Textarea::make('note')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Waktu')
                    ->sortable(),
                TextColumn::make('uplink.username')
                    ->label('Uplink (Penerima)')
                    ->searchable(),
                TextColumn::make('downlink.username')
                    ->label('Downlink (Pembeli)')
                    ->searchable(),
                TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable(),
                TextColumn::make('amount')
                    ->money('idr')
                    ->label('Komisi')
                    ->sortable(),
                BadgeColumn::make('note')
                    ->colors([
                        'success' => 'Commission',
                    ]),
            ])
            ->filters([
                SelectFilter::make('uplink_id')
                    ->relationship('uplink', 'username')
                    ->searchable()
                    ->label('Filter Uplink'),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
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
            'index' => Pages\ListAffiliateHistories::route('/'),
        ];
    }
}
