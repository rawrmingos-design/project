<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Username copied')
                    ->prefix('@'),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                    
                BadgeColumn::make('role')
                    ->label('Role/Tier')
                    ->colors([
                        'danger' => 'Admin',
                        'info' => 'Platinum',
                        'warning' => 'Gold',
                        'success' => 'Member',
                    ])
                    ->icons([
                        'heroicon-o-shield-check' => 'Admin',
                        'heroicon-o-star' => 'Platinum',
                        'heroicon-o-trophy' => 'Gold',
                        'heroicon-o-user' => 'Member',
                    ]),
                    
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                    
                TextColumn::make('no_wa')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-phone'),
                    
                IconColumn::make('api_key')
                    ->label('API Key')
                    ->boolean()
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->getStateUsing(fn ($record) => !empty($record->api_key)),
                    
                TextColumn::make('idgame')
                    ->label('Game ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(20),
                    
                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role/Tier')
                    ->options([
                        'Admin' => 'Admin',
                        'Platinum' => 'Platinum Member',
                        'Gold' => 'Gold Member',
                        'Member' => 'Regular Member',
                    ]),
                    
                Filter::make('has_balance')
                    ->label('Has Balance')
                    ->query(fn (Builder $query): Builder => $query->where('balance', '>', 0))
                    ->toggle(),
                    
                Filter::make('has_api_key')
                    ->label('Has API Key')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('api_key'))
                    ->toggle(),
                    
                Filter::make('premium_tiers')
                    ->label('Premium Tiers Only')
                    ->query(fn (Builder $query): Builder => $query->whereIn('role', ['Gold', 'Platinum']))
                    ->toggle(),
                    
                Filter::make('balance_range')
                    ->form([
                        TextInput::make('balance_from')
                            ->label('Balance From')
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('balance_to')
                            ->label('Balance To')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['balance_from'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '>=', $balance),
                            )
                            ->when(
                                $data['balance_to'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '<=', $balance),
                            );
                    }),
                    
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Registered from'),
                        DatePicker::make('created_until')
                            ->label('Registered until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                
                Action::make('add_balance')
                    ->label('Add Balance')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('amount')
                            ->label('Amount to Add')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->step(1000),
                    ])
                    ->action(function ($record, array $data) {
                        $record->increment('balance', $data['amount']);
                        
                        Notification::make()
                            ->title('Balance Added')
                            ->body("Rp " . number_format($data['amount'], 0, ',', '.') . " added to {$record->name}'s balance")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                    
                Action::make('upgrade_tier')
                    ->label('Upgrade Tier')
                    ->icon('heroicon-o-arrow-up')
                    ->color('warning')
                    ->form([
                        SelectFilter::make('new_role')
                            ->label('New Tier')
                            ->options([
                                'Platinum' => 'Platinum Member',
                                'Gold' => 'Gold Member',
                                'Member' => 'Regular Member',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $oldRole = $record->role;
                        $record->update(['role' => $data['new_role']]);
                        
                        Notification::make()
                            ->title('Tier Updated')
                            ->body("{$record->name} upgraded from {$oldRole} to {$data['new_role']}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->role !== 'Admin')
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    \Filament\Actions\BulkAction::make('upgrade_to_gold')
                        ->label('Upgrade to Gold')
                        ->icon('heroicon-o-trophy')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['role' => 'Gold']);
                            
                            Notification::make()
                                ->title('Bulk Tier Upgrade')
                                ->body("{$count} users upgraded to Gold tier")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                        
                    \Filament\Actions\BulkAction::make('upgrade_to_platinum')
                        ->label('Upgrade to Platinum')
                        ->icon('heroicon-o-star')
                        ->color('info')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each->update(['role' => 'Platinum']);
                            
                            Notification::make()
                                ->title('Bulk Tier Upgrade')
                                ->body("{$count} users upgraded to Platinum tier")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                        
                    \Filament\Actions\BulkAction::make('add_bonus_balance')
                        ->label('Add Bonus Balance')
                        ->icon('heroicon-o-gift')
                        ->color('success')
                        ->form([
                            TextInput::make('bonus_amount')
                                ->label('Bonus Amount')
                                ->numeric()
                                ->required()
                                ->prefix('Rp')
                                ->step(1000)
                                ->default(10000),
                        ])
                        ->action(function ($records, array $data) {
                            $count = $records->count();
                            $records->each->increment('balance', $data['bonus_amount']);
                            
                            Notification::make()
                                ->title('Bonus Balance Added')
                                ->body("Rp " . number_format($data['bonus_amount'], 0, ',', '.') . " bonus added to {$count} users")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
