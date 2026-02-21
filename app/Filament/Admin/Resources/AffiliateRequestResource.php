<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AffiliateRequestResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use BackedEnum;
use UnitEnum;
class AffiliateRequestResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Permintaan Affiliate';
    
    protected static ?string $pluralLabel = 'Permintaan Affiliate';

    protected static UnitEnum|string|null $navigationGroup = 'Affiliate System';
    
    protected static ?string $slug = 'affiliate-requests';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('affiliate_status', 'pending');
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled(),
                TextInput::make('username')
                    ->disabled(),
                TextInput::make('email')
                    ->disabled(),
                Select::make('affiliate_status')
                    ->options([
                        'inactive' => 'Inactive',
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Tgl Daftar'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                BadgeColumn::make('affiliate_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve')
                    ->label('Terima')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->action(function (User $record) {
                        $record->affiliate_status = 'active';
                        // Generate referral code if not exists
                        if (!$record->referral_code) {
                            $record->referral_code = 'REF-' . strtoupper(\Str::random(6));
                        }
                        $record->save();
                    })
                    ->requiresConfirmation(),
                    
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->action(function (User $record) {
                        $record->affiliate_status = 'rejected';
                        $record->save();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListAffiliateRequests::route('/'),
        ];
    }
}
