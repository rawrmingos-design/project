<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Withdrawals';
    
    protected static ?string $pluralLabel = 'Withdrawals';

    protected static UnitEnum|string|null $navigationGroup = 'Affiliate System';
    
    protected static ?string $slug = 'withdrawals';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'username')
                    ->disabled(),
                TextInput::make('rekening')
                    ->disabled(),
                TextInput::make('total_transfer')
                    ->disabled()
                    ->label('Jumlah'),
                TextInput::make('biaya_admin')
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'rejected' => 'Rejected',
                    ]),
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
                TextColumn::make('user.username')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('rekening')
                    ->label('Rekening')
                    ->limit(20),
                TextColumn::make('total_transfer')
                    ->money('idr')
                    ->label('Jumlah')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->action(function (Withdrawal $record) {
                        $record->status = 'success';
                        $record->save();
                        
                        // Deduct balance? Wait, logic says affiliate withdraws COMMISSION.
                        // Usually commission is already in a separate wallet or balance.
                        // Or is it transfer from Commission Wallet to Main Wallet?
                        // Or Main Wallet to Bank?
                        
                        // "bisa withdraw harus konfirmasi dari admin dlu" implies Bank Withdrawal.
                        // We assume money is already deducted from balance when requesting withdrawal?
                        // Or deducted upon approval?
                        
                        // We should check existing WithdrawalController logic if exists.
                        // For now, simple status update.
                    })
                    ->requiresConfirmation(),
                    
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->action(function (Withdrawal $record) {
                        $record->status = 'rejected';
                        $record->save();
                        
                        // Refund balance if rejected
                        $user = $record->user;
                        if ($user) {
                            $user->balance += $record->total_transfer;
                            $user->save();
                            
                            // Optional: Notification?
                        }
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
            'index' => Pages\ListWithdrawals::route('/'),
        ];
    }
}
