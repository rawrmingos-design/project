<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                    
                TextColumn::make('no_wa')
                    ->label('WhatsApp')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->toggleable(),
                    
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                    
                BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'danger' => 'Admin',
                        'success' => 'Platinum',
                        'warning' => 'Gold',
                        'primary' => 'Member',
                    ])
                    ->icons([
                        'heroicon-o-shield-check' => 'Admin',
                        'heroicon-o-star' => 'Platinum',
                        'heroicon-o-trophy' => 'Gold',
                        'heroicon-o-user' => 'Member',
                    ]),
                    
                TextColumn::make('api_key')
                    ->label('API Key')
                    ->limit(20)
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not generated'),
                    
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('User Role')
                    ->options([
                        'Admin' => 'Admin',
                        'Member' => 'Member',
                        'Gold' => 'Gold',
                        'Platinum' => 'Platinum',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make(),
                
                Action::make('adjust_balance')
                    ->label('Adjust Balance')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->form([
                        TextInput::make('amount')
                            ->label('Amount')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->helperText('Use negative value to deduct, positive to add'),
                    ])
                    ->action(function ($record, array $data) {
                        $newBalance = $record->balance + $data['amount'];
                        $record->update(['balance' => max(0, $newBalance)]);
                        
                        Notification::make()
                            ->title('Balance Adjusted')
                            ->body("New balance: Rp " . number_format($newBalance, 0, ',', '.'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
