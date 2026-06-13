<?php

namespace App\Filament\Admin\Resources\ResellerOrders\Pages;

use App\Filament\Admin\Resources\ResellerOrders\ResellerOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Illuminate\Support\HtmlString;

class ViewResellerOrder extends ViewRecord
{
    protected static string $resource = ResellerOrderResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Reseller Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('resellerIntegration.user.name')
                                    ->label('Reseller Name')
                                    ->weight('bold'),
                                
                                TextEntry::make('resellerIntegration.user.email')
                                    ->label('Reseller Email')
                                    ->icon('heroicon-m-envelope')
                                    ->copyable(),
                                
                                TextEntry::make('reseller_integration_id')
                                    ->label('Integration ID')
                                    ->badge()
                                    ->color('gray'),
                                
                                TextEntry::make('resellerIntegration.api_key_prefix')
                                    ->label('API Key')
                                    ->formatStateUsing(fn ($state) => $state ? substr($state, 0, 15) . '...' : '-')
                                    ->copyable(),
                                
                                TextEntry::make('is_sandbox')
                                    ->label('Order Mode')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => $record->is_sandbox ? 'Sandbox (Test)' : 'Live (Production)')
                                    ->color(fn ($record) => $record->is_sandbox ? 'warning' : 'success')
                                    ->icon(fn ($record) => $record->is_sandbox ? 'heroicon-o-beaker' : 'heroicon-o-check-circle'),
                                
                                TextEntry::make('environment')
                                    ->label('Environment')
                                    ->badge()
                                    ->default('production')
                                    ->formatStateUsing(fn ($state) => $state ?: 'production'),
                            ]),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-server-stack'),

                Section::make('Order Details')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('order_id')
                                    ->label('Order ID')
                                    ->weight('bold')
                                    ->copyable(),
                                
                                TextEntry::make('display_order_id')
                                    ->label('Display Order ID')
                                    ->weight('medium')
                                    ->copyable()
                                    ->visible(fn ($record) => $record->display_order_id !== $record->order_id),
                                
                                TextEntry::make('created_at')
                                    ->label('Order Date')
                                    ->dateTime('d M Y H:i:s')
                                    ->icon('heroicon-m-clock'),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('layanan')
                                    ->label('Product/Service')
                                    ->weight('medium'),
                                
                                TextEntry::make('harga')
                                    ->label('Amount')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-shopping-cart'),

                Section::make('Game Account Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('user_id')
                                    ->label('Game Account ID')
                                    ->copyable(),
                                
                                TextEntry::make('zone')
                                    ->label('Zone/Server')
                                    ->default('-')
                                    ->visible(fn ($record) => !empty($record->zone) && $record->zone !== 'N/A'),
                                
                                TextEntry::make('nickname')
                                    ->label('Nickname')
                                    ->default('-'),
                            ]),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-user-circle'),

                Section::make('Status Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Provider Status')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => $record->status_display_label)
                                    ->color(fn ($record) => $record->status_badge_color),
                                
                                TextEntry::make('pembayaran.status')
                                    ->label('Payment Status')
                                    ->badge()
                                    ->getStateUsing(fn ($record) => optional($record->pembayaran)->status ?: '-')
                                    ->color(fn ($state) => $state === 'Lunas' ? 'success' : 'warning'),
                                
                                TextEntry::make('provider_order_id')
                                    ->label('Provider Order ID')
                                    ->copyable()
                                    ->default('-'),
                            ]),
                        
                        TextEntry::make('keterangan_sn')
                            ->label('Serial Number / Notes')
                            ->columnSpanFull()
                            ->default('-')
                            ->html()
                            ->formatStateUsing(fn ($state) => nl2br(e($state))),
                    ])
                    ->collapsible()
                    ->icon('heroicon-o-clipboard-document-check'),

                Section::make('Payment Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('pembayaran.metode')
                                    ->label('Payment Method')
                                    ->badge()
                                    ->default('-'),
                                
                                TextEntry::make('pembayaran.no_pembeli')
                                    ->label('Customer Phone')
                                    ->copyable()
                                    ->default('-'),
                                
                                TextEntry::make('profit')
                                    ->label('Profit')
                                    ->money('IDR')
                                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                                    ->visible(fn ($state) => $state != 0),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-credit-card'),

                Section::make('Provider Response')
                    ->schema([
                        TextEntry::make('log')
                            ->label('Provider Log')
                            ->columnSpanFull()
                            ->default('-')
                            ->formatStateUsing(function ($state) {
                                if (empty($state)) return '-';
                                
                                // Try to format as JSON if possible
                                $decoded = json_decode($state, true);
                                if (json_last_error() === JSON_ERROR_NONE) {
                                    return new HtmlString('<pre class="text-xs overflow-auto max-h-96 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">' . 
                                        e(json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . 
                                        '</pre>');
                                }
                                
                                return new HtmlString('<pre class="text-xs overflow-auto max-h-96 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">' . 
                                    e($state) . 
                                    '</pre>');
                            }),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->icon('heroicon-o-code-bracket'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Back to List')
                ->url(ResellerOrderResource::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
