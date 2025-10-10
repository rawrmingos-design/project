<?php

namespace App\Filament\Admin\Resources\Pembelians\Pages;

use App\Filament\Admin\Resources\Pembelians\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process_order')
                ->label('Process Order')
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
                
            Actions\Action::make('cancel_order')
                ->label('Cancel Order')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn ($record) => in_array($record->status, ['Pending', 'Processing']))
                ->action(function ($record) {
                    $record->update(['status' => 'Failed', 'message' => 'Cancelled by admin']);
                    Notification::make()
                        ->title('Order cancelled successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
                
            Actions\Action::make('refund')
                ->label('Refund')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn ($record) => $record->status === 'Success')
                ->action(function ($record) {
                    // Add refund logic here
                    $record->update(['message' => 'Refund processed by admin']);
                    Notification::make()
                        ->title('Refund processed successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }
}
