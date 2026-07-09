<?php

namespace App\Filament\Admin\Resources\Tenants\RelationManagers;

use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionInvoiceEvent;
use App\Tenancy\DuitkuSubscriptionPaymentService;
use App\Tenancy\TenantProvisioningService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SubscriptionInvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptionInvoices';

    protected static ?string $title = 'Subscription Invoices';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('subscription.tier')
                    ->label('Tier')
                    ->badge(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        SubscriptionInvoice::STATUS_PAID => 'success',
                        SubscriptionInvoice::STATUS_PENDING => 'warning',
                        SubscriptionInvoice::STATUS_EXPIRED => 'danger',
                        SubscriptionInvoice::STATUS_CANCELLED => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('gateway')
                    ->badge()
                    ->sortable(),
                TextColumn::make('gateway_ref')
                    ->label('Gateway Ref')
                    ->copyable()
                    ->searchable()
                    ->limit(28),
                TextColumn::make('duitku_reference')
                    ->label('Duitku Ref')
                    ->state(fn (SubscriptionInvoice $record): string => (string) data_get($record->metadata, 'duitku.reference', '-'))
                    ->copyable()
                    ->limit(28)
                    ->toggleable(),
                TextColumn::make('duitku_last_callback')
                    ->label('Last Callback')
                    ->state(fn (SubscriptionInvoice $record): string => (string) data_get($record->metadata, 'duitku.last_callback.result_code', '-'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->label('Paid')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\Action::make('view_events')
                        ->label('View Events')
                        ->icon('heroicon-o-clock')
                        ->modalHeading(fn (SubscriptionInvoice $record): string => 'Invoice Events #' . $record->id)
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(fn (SubscriptionInvoice $record): HtmlString => $this->eventsHtml($record)),

                    Actions\Action::make('retry_duitku')
                        ->label('Retry Duitku')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (SubscriptionInvoice $record): bool => $record->gateway === 'duitku'
                            && in_array($record->status, [SubscriptionInvoice::STATUS_PENDING, SubscriptionInvoice::STATUS_EXPIRED], true)
                            && (int) $record->amount > 0)
                        ->requiresConfirmation()
                        ->modalHeading('Retry Duitku Invoice')
                        ->modalDescription('Buat ulang payment URL Duitku untuk invoice ini. Reference dan payment URL terbaru akan disimpan di metadata invoice.')
                        ->action(fn (SubscriptionInvoice $record) => $this->retryDuitkuInvoice($record)),

                    Actions\Action::make('mark_paid')
                        ->label('Mark Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (SubscriptionInvoice $record): bool => $record->status !== SubscriptionInvoice::STATUS_PAID)
                        ->form([
                            TextInput::make('gateway_ref')
                                ->label('Gateway Ref Override')
                                ->placeholder('Kosongkan untuk memakai gateway ref saat ini')
                                ->maxLength(255),
                            Textarea::make('note')
                                ->label('Admin Note')
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Mark Invoice Paid')
                        ->modalDescription('Invoice akan ditandai paid, subscription dan tenant akan diaktifkan, owner akan menjadi Gold.')
                        ->action(fn (SubscriptionInvoice $record, array $data) => $this->markPaid($record, $data)),

                    Actions\Action::make('mark_expired')
                        ->label('Mark Expired')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (SubscriptionInvoice $record): bool => $record->status === SubscriptionInvoice::STATUS_PENDING)
                        ->form([
                            Textarea::make('note')
                                ->label('Admin Note')
                                ->rows(3)
                                ->maxLength(1000),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Mark Invoice Expired')
                        ->modalDescription('Invoice pending akan ditandai expired tanpa mengaktifkan tenant.')
                        ->action(fn (SubscriptionInvoice $record, array $data) => $this->markExpired($record, $data)),
                ]),
            ])
            ->defaultSort('id', 'desc')
            ->striped();
    }

    private function retryDuitkuInvoice(SubscriptionInvoice $record): void
    {
        $metadata = $record->metadata ?: [];
        $retryCount = (int) data_get($metadata, 'duitku.retry_count', 0) + 1;

        $invoice = app(DuitkuSubscriptionPaymentService::class)->createAndStoreInvoice($record, [
            'duitku' => [
                'retry_count' => $retryCount,
                'retried_at' => now()->toIso8601String(),
                'retried_by' => Auth::id(),
            ],
        ]);

        SubscriptionInvoiceEvent::record(
            $invoice,
            SubscriptionInvoiceEvent::TYPE_RETRY,
            $invoice->status,
            (string) data_get($invoice->metadata, 'duitku.reference', $invoice->gateway_ref),
            data_get($invoice->metadata, 'duitku'),
            ['admin_user_id' => Auth::id(), 'retry_count' => $retryCount],
            Auth::id(),
        );

        Notification::make()
            ->title('Duitku invoice berhasil dibuat ulang')
            ->body((string) data_get($invoice->metadata, 'duitku.payment_url', 'Payment URL tersimpan di metadata invoice.'))
            ->success()
            ->send();
    }

    private function markPaid(SubscriptionInvoice $record, array $data): void
    {
        $note = trim((string) ($data['note'] ?? ''));
        $gatewayRef = trim((string) ($data['gateway_ref'] ?? '')) ?: null;
        $metadataMerge = [
            'admin_action' => [
                'type' => 'mark_paid',
                'user_id' => Auth::id(),
                'note' => $note,
                'at' => now()->toIso8601String(),
            ],
        ];

        $invoice = app(TenantProvisioningService::class)->markInvoicePaid($record, $gatewayRef, $metadataMerge);

        SubscriptionInvoiceEvent::record(
            $invoice,
            SubscriptionInvoiceEvent::TYPE_ADMIN_ACTION,
            $invoice->status,
            $gatewayRef ?: $invoice->gateway_ref,
            null,
            $metadataMerge['admin_action'],
            Auth::id(),
        );

        Notification::make()
            ->title('Invoice ditandai paid')
            ->body('Tenant dan subscription sudah aktif.')
            ->success()
            ->send();
    }

    private function markExpired(SubscriptionInvoice $record, array $data): void
    {
        $note = trim((string) ($data['note'] ?? ''));
        $metadataMerge = [
            'admin_action' => [
                'type' => 'mark_expired',
                'user_id' => Auth::id(),
                'note' => $note,
                'at' => now()->toIso8601String(),
            ],
        ];

        $invoice = app(TenantProvisioningService::class)->markInvoiceExpired($record, $metadataMerge);

        SubscriptionInvoiceEvent::record(
            $invoice,
            SubscriptionInvoiceEvent::TYPE_ADMIN_ACTION,
            $invoice->status,
            $invoice->gateway_ref,
            null,
            $metadataMerge['admin_action'],
            Auth::id(),
        );

        Notification::make()
            ->title('Invoice ditandai expired')
            ->success()
            ->send();
    }

    private function eventsHtml(SubscriptionInvoice $record): HtmlString
    {
        $events = $record->events()->latest()->limit(20)->get();

        if ($events->isEmpty()) {
            return new HtmlString('<p class="text-sm text-gray-500">Belum ada event untuk invoice ini.</p>');
        }

        $html = '<div class="space-y-3">';

        foreach ($events as $event) {
            $payload = json_encode($event->payload ?: $event->meta ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $html .= '<div class="rounded-lg border border-gray-200 p-3">';
            $html .= '<div class="text-sm font-semibold">' . e($event->type) . ' · ' . e((string) ($event->status ?? '-')) . '</div>';
            $html .= '<div class="text-xs text-gray-500">' . e($event->created_at?->format('d M Y H:i:s') ?? '-') . ' · ref: ' . e((string) ($event->reference ?? '-')) . '</div>';
            $html .= '<pre class="mt-2 max-h-56 overflow-auto rounded bg-gray-950 p-3 text-xs text-gray-100">' . e($payload ?: '{}') . '</pre>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }
}
