<?php

namespace App\Filament\Admin\Resources\Deposits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use App\Models\User;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_id')
                    ->label('Order ID')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                    
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                    
                BadgeColumn::make('metode')
                    ->label('Payment Method')
                    ->colors([
                        'primary' => ['BCA', 'BNI', 'BRI', 'Mandiri'],
                        'success' => ['QRIS'],
                        'warning' => ['OVO', 'DANA', 'GoPay', 'ShopeePay'],
                    ]),
                    
                TextColumn::make('no_pembayaran')
                    ->label('Payment Number')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(),
                    
                TextColumn::make('jumlah')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color('success'),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Success',
                        'warning' => 'Pending',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Success',
                        'heroicon-o-clock' => 'Pending',
                    ]),
                    
                TextColumn::make('created_at')
                    ->label('Request Date')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Pending' => 'Pending',
                        'Success' => 'Success',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('metode')
                    ->label('Payment Method')
                    ->options([
                        'BCA' => 'BCA',
                        'BNI' => 'BNI',
                        'BRI' => 'BRI',
                        'Mandiri' => 'Mandiri',
                        'QRIS' => 'QRIS',
                        'OVO' => 'OVO',
                        'DANA' => 'DANA',
                        'GoPay' => 'GoPay',
                        'ShopeePay' => 'ShopeePay',
                    ])
                    ->multiple(),
                    
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('From Date'),
                        DatePicker::make('created_until')
                            ->label('Until Date'),
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
                EditAction::make(),
                
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'Pending')
                    ->action(function ($record) {
                        // Find user and update balance
                        $user = User::where('username', $record->username)->first();
                        
                        if ($user) {
                            $user->balance += $record->jumlah;
                            $user->save();
                            
                            $record->update(['status' => 'Success']);
                            
                            Notification::make()
                                ->title('Deposit Approved')
                                ->body("Rp " . number_format($record->jumlah, 0, ',', '.') . " has been added to {$user->name}'s balance")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('User Not Found')
                                ->body("Cannot find user with username: {$record->username}")
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Deposit')
                    ->modalDescription('This will add the deposit amount to user balance and mark as success.')
                    ->modalSubmitActionLabel('Approve'),
                    
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'Pending')
                    ->action(function ($record) {
                        $record->delete();
                        
                        Notification::make()
                            ->title('Deposit Rejected')
                            ->body('Deposit request has been deleted')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reject Deposit')
                    ->modalDescription('This will permanently delete the deposit request.')
                    ->modalSubmitActionLabel('Reject'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = 0;
                            
                            foreach ($records->where('status', 'Pending') as $record) {
                                $user = User::where('username', $record->username)->first();
                                
                                if ($user) {
                                    $user->balance += $record->jumlah;
                                    $user->save();
                                    
                                    $record->update(['status' => 'Success']);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title('Bulk Approval Completed')
                                ->body("{$count} deposits have been approved")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                        
                    DeleteBulkAction::make()
                        ->label('Reject Selected'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->deferLoading(! app()->runningUnitTests())
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
