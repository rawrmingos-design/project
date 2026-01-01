<?php

namespace App\Filament\Admin\Resources\Pembelians\Pages;

use App\Filament\Admin\Resources\Pembelians\PembelianResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Order Information')
                    ->icon('heroicon-o-shopping-cart')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('order_id')
                            ->label('Order ID')
                            ->copyable()
                            ->weight('bold'),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Success' => 'success',
                                'Pending' => 'warning',
                                'Processing' => 'info',
                                'Failed' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('username')
                            ->label('Customer')
                            ->default('N/A'),

                        TextEntry::make('layanan')
                            ->label('Product/Service'),

                        TextEntry::make('harga')
                            ->label('Amount')
                            ->money('IDR')
                            ->color('success')
                            ->weight('bold'),
                    ])
                    ->collapsible(),

                Section::make('Game Details')
                    ->icon('heroicon-o-puzzle-piece')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('user_id')
                            ->label('Server ID')
                            ->default('N/A'),

                        TextEntry::make('zone')
                            ->label('Zone')
                            ->default('N/A'),

                        TextEntry::make('nickname')
                            ->label('Game Nickname')
                            ->default('N/A'),

                        TextEntry::make('tipe_transaksi')
                            ->label('Transaction Type')
                            ->badge(),
                    ])
                    ->collapsible(),

                Section::make('Transaction Details')
                    ->icon('heroicon-o-credit-card')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('provider_order_id')
                            ->label('Provider Order ID')
                            ->copyable()
                            ->default('N/A'),

                        TextEntry::make('voucher')
                            ->label('Voucher Code')
                            ->copyable()
                            ->default('N/A'),

                        TextEntry::make('profit')
                            ->label('Profit')
                            ->money('IDR')
                            ->color('info')
                            ->weight('bold'),

                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->default('N/A'),
                    ])
                    ->collapsible(),

                Section::make('System Information')
                    ->icon('heroicon-o-cog')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('log')
                            ->label('System Log')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => $state ?: 'No log available'),

                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d M Y H:i:s'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('d M Y H:i:s'),
                    ])
                    ->collapsible(),
            ]);
    }

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
                    $record->update(['status' => 'Failed', 'log' => 'Cancelled by admin at ' . now()->format('Y-m-d H:i:s')]);
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
                    $record->update(['log' => 'Refund processed by admin at ' . now()->format('Y-m-d H:i:s')]);
                    Notification::make()
                        ->title('Refund processed successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }
}
