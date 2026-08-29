<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InvoiceNotificationDeliveryResource\Pages;
use App\Jobs\SendInvoiceNotificationJob;
use App\Models\InvoiceNotificationDelivery;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use UnitEnum;
use BackedEnum;

class InvoiceNotificationDeliveryResource extends Resource
{
    protected static ?string $model = InvoiceNotificationDelivery::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static UnitEnum|string|null $navigationGroup = 'Notification Management';
    protected static ?string $navigationLabel = 'Invoice Deliveries';
    protected static ?string $modelLabel = 'Invoice Delivery';
    protected static ?string $pluralModelLabel = 'Invoice Deliveries';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')->label('Order ID')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('channel')->badge()->sortable(),
                Tables\Columns\TextColumn::make('transition')->badge()->sortable(),
                Tables\Columns\TextColumn::make('template_slug')->label('Template')->sortable(),
                Tables\Columns\TextColumn::make('recipient')->label('Recipient')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('attempts')->sortable(),
                Tables\Columns\TextColumn::make('last_error')->label('Last Error')->limit(60)->tooltip(fn ($record) => $record->last_error),
                Tables\Columns\TextColumn::make('next_attempt_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    InvoiceNotificationDelivery::STATUS_PENDING => 'Pending',
                    InvoiceNotificationDelivery::STATUS_SENDING => 'Sending',
                    InvoiceNotificationDelivery::STATUS_SENT => 'Sent',
                    InvoiceNotificationDelivery::STATUS_FAILED => 'Failed',
                ]),
                SelectFilter::make('channel')->options([
                    InvoiceNotificationDelivery::CHANNEL_WHATSAPP => 'WhatsApp',
                    InvoiceNotificationDelivery::CHANNEL_EMAIL => 'Email',
                ]),
            ])
            ->actions([
                Action::make('resend')
                    ->label('Resend')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (InvoiceNotificationDelivery $record): bool => $record->status !== InvoiceNotificationDelivery::STATUS_SENDING)
                    ->requiresConfirmation()
                    ->action(function (InvoiceNotificationDelivery $record): void {
                        SendInvoiceNotificationJob::dispatch($record->getKey(), true);
                        Notification::make()->title('Notification queued for resend')->success()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInvoiceNotificationDeliveries::route('/')];
    }
}
