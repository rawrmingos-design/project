<?php

namespace App\Filament\Admin\Resources\ResellerCallbackProfiles\RelationManagers;

use App\Models\ResellerCallbackDelivery;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';

    protected static ?string $title = 'Delivery Logs';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('last_attempted_at')
                    ->label('Attempted')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('environment')
                    ->label('Env')
                    ->badge()
                    ->color(fn (?string $state): string => $state === 'sandbox' ? 'info' : 'gray')
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
                TextColumn::make('event_name')
                    ->label('Event')
                    ->badge()
                    ->copyable(),
                TextColumn::make('order_id')
                    ->label('Invoice')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'delivered' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
                TextColumn::make('last_response_status')
                    ->label('HTTP')
                    ->placeholder('-'),
                TextColumn::make('last_error')
                    ->label('Error')
                    ->limit(90)
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('last_response_body')
                    ->label('Response Body')
                    ->limit(140)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('payload_summary')
                    ->label('Payload')
                    ->state(fn (ResellerCallbackDelivery $record): string => static::payloadSummary($record))
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }

    private static function payloadSummary(ResellerCallbackDelivery $record): string
    {
        $payload = $record->payload ?? [];

        return sprintf(
            'invoice=%s status=%s ref=%s',
            (string) ($payload['invoiceNumber'] ?? $record->order_id ?? '-'),
            (string) ($payload['statusCode'] ?? '-'),
            (string) ($payload['referenceNumber'] ?? $record->reference_number ?? '-'),
        );
    }
}
