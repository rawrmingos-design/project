<?php

namespace App\Filament\Admin\Resources\Pembelians\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

class PembeliansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_id')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                    
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                    
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('layanan')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Success',
                        'warning' => 'Pending',
                        'info' => 'Processing',
                        'danger' => 'Failed',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Success',
                        'heroicon-o-clock' => 'Pending',
                        'heroicon-o-arrow-path' => 'Processing',
                        'heroicon-o-x-circle' => 'Failed',
                    ])
                    ->sortable(),
                    
                TextColumn::make('harga')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),
                    
                TextColumn::make('profit')
                    ->label('Profit')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                    
                TextColumn::make('zone')
                    ->label('Zone/Server')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('N/A'),
                    
                TextColumn::make('nickname')
                    ->label('Nickname')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('N/A'),
                    
                TextColumn::make('tipe_transaksi')
                    ->label('Type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Success' => 'Success',
                        'Pending' => 'Pending',
                        'Processing' => 'Processing',
                        'Failed' => 'Failed',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('tipe_transaksi')
                    ->label('Transaction Type')
                    ->options([
                        'game' => 'Game',
                        'pulsa' => 'Pulsa',
                        'data' => 'Data',
                        'pln' => 'PLN',
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
                    
                Filter::make('amount_range')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('amount_from')
                            ->label('Min Amount')
                            ->numeric(),
                        \Filament\Forms\Components\TextInput::make('amount_until')
                            ->label('Max Amount')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('harga', '>=', $amount),
                            )
                            ->when(
                                $data['amount_until'],
                                fn (Builder $query, $amount): Builder => $query->where('harga', '<=', $amount),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View Detail'),
                    
                Action::make('process')
                    ->label('Process')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'Pending')
                    ->action(function ($record) {
                        $record->update(['status' => 'Processing']);
                        Notification::make()
                            ->title('Order processed successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                    
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['Pending', 'Processing']))
                    ->action(function ($record) {
                        $record->update(['status' => 'Failed', 'log' => 'Cancelled by admin at ' . now()->format('Y-m-d H:i:s')]);
                        Notification::make()
                            ->title('Order cancelled successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                    
                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'Success')
                    ->action(function ($record) {
                        // Add refund logic here
                        $record->update(['log' => 'Refund processed by admin at ' . now()->format('Y-m-d H:i:s')]);
                        Notification::make()
                            ->title('Refund processed successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('export_excel')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            // Export logic will be implemented
                            Notification::make()
                                ->title('Export started')
                                ->body('Excel file will be generated shortly')
                                ->info()
                                ->send();
                        }),
                        
                    BulkAction::make('bulk_process')
                        ->label('Process Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->where('status', 'Pending')->count();
                            $records->where('status', 'Pending')->each(function ($record) {
                                $record->update(['status' => 'Processing']);
                            });
                            
                            Notification::make()
                                ->title('Bulk process completed')
                                ->body("{$count} orders have been processed")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                        
                    BulkAction::make('bulk_cancel')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $count = $records->whereIn('status', ['Pending', 'Processing'])->count();
                            $records->whereIn('status', ['Pending', 'Processing'])->each(function ($record) {
                                $record->update(['status' => 'Failed', 'log' => 'Bulk cancelled by admin at ' . now()->format('Y-m-d H:i:s')]);
                            });
                            
                            Notification::make()
                                ->title('Bulk cancel completed')
                                ->body("{$count} orders have been cancelled")
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
