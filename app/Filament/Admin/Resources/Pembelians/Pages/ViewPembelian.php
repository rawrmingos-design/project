<?php

namespace App\Filament\Admin\Resources\Pembelians\Pages;

use App\Filament\Admin\Resources\Pembelians\PembelianResource;
use App\Models\ProviderPath;
use App\Services\OrderProcessingService;
use App\Services\ResetDomainService;
use App\Support\PembelianStatus;
use DomainException;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ViewPembelian extends ViewRecord
{
    protected static string $resource = PembelianResource::class;

    public function getTitle(): string
    {
        return 'Lihat ' . ($this->record->display_order_id ?: $this->record->order_id);
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Order Information')
                    ->schema([
                        TextEntry::make('order_id')
                            ->label('Order ID')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('display_order_id')
                            ->label('Display Invoice')
                            ->copyable(),
                        TextEntry::make('status_display_label')
                            ->label('Status')
                            ->badge()
                            ->color(fn (): string => $this->record->status_badge_color)
                            ->icon(fn (): string => $this->record->status_icon),
                        TextEntry::make('customer_name')
                            ->label('Customer')
                            ->state(fn (): string => $this->record->user->name ?? 'N/A'),
                        TextEntry::make('username')
                            ->label('Username')
                            ->default('Anonim'),
                        TextEntry::make('layanan')
                            ->label('Product / Service')
                            ->default('N/A')
                            ->columnSpanFull(),
                        TextEntry::make('harga')
                            ->label('Amount')
                            ->money('IDR')
                            ->weight('bold'),
                        TextEntry::make('current_provider')
                            ->label('Current Provider')
                            ->state(fn (): string => $this->getCurrentProviderLabel()),
                    ])
                    ->columns(2),

                Section::make('Game Details')
                    ->schema([
                        TextEntry::make('user_id')
                            ->label('User ID')
                            ->default('N/A'),
                        TextEntry::make('zone')
                            ->label('Zone / Server')
                            ->default('N/A'),
                        TextEntry::make('nickname')
                            ->label('Game Nickname')
                            ->default('N/A'),
                        TextEntry::make('tipe_transaksi')
                            ->label('Transaction Type')
                            ->formatStateUsing(fn (?string $state): string => filled($state) ? Str::headline($state) : 'N/A')
                            ->badge(),
                    ])
                    ->columns(2),

                Section::make('Reset Context')
                    ->description('Reset state stays read-only on the page body. Any allowed adjustments happen through header modal actions.')
                    ->schema([
                        TextEntry::make('reset_status_label')
                            ->label('Reset Status')
                            ->state(fn (): string => $this->getResetStatusLabel())
                            ->badge(),
                        TextEntry::make('invoice_version')
                            ->label('Invoice Version'),
                        TextEntry::make('active_attempt_reference')
                            ->label('Active Attempt Reference')
                            ->state(fn (): string => $this->record->active_attempt_reference ?: $this->record->display_order_id)
                            ->copyable(),
                        TextEntry::make('reset_reason')
                            ->label('Reset Reason')
                            ->default('N/A'),
                    ])
                    ->columns(2),

                Section::make('Transaction Details')
                    ->schema([
                        TextEntry::make('provider_order_id')
                            ->label('Provider Order ID')
                            ->default('N/A')
                            ->copyable(),
                        TextEntry::make('active_provider_sku')
                            ->label('Active Provider SKU')
                            ->default('N/A'),
                        TextEntry::make('voucher')
                            ->label('Voucher Code')
                            ->default('N/A')
                            ->copyable(),
                        TextEntry::make('profit')
                            ->label('Profit')
                            ->money('IDR'),
                        TextEntry::make('ip_address')
                            ->label('IP Address')
                            ->default('N/A')
                            ->copyable(),
                    ])
                    ->columns(2),

                Section::make('System Information')
                    ->schema([
                        TextEntry::make('keterangan_sn')
                            ->label('Keterangan / SN')
                            ->state(fn (): string => $this->record->keterangan_sn ?: ($this->record->voucher ?: 'N/A'))
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('log')
                            ->label('System Log')
                            ->default('N/A')
                            ->fontFamily('mono')
                            ->wrap()
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('d M Y H:i:s'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reset_invoice')
                ->label('Reset Invoice')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->record->isResetEligible())
                ->disabled(fn (): bool => count($this->providerSelectOptions) === 0)
                ->tooltip(fn (): ?string => count($this->providerSelectOptions) === 0
                    ? 'Tambahkan provider path cadangan di Produk Management terlebih dahulu.'
                    : null)
                ->requiresConfirmation()
                ->modalHeading('Reset Invoice')
                ->modalDescription(fn (): string => count($this->providerSelectOptions) === 0
                    ? 'Belum ada provider path cadangan yang valid. Tambahkan provider path cadangan di Produk Management, lalu kembali ke transaksi ini.'
                    : 'Create the next reset attempt while keeping the canonical order ID stable.')
                ->form([
                    Placeholder::make('current_provider')
                        ->label('Current Provider')
                        ->content(fn (): string => $this->getCurrentProviderLabel()),
                    Placeholder::make('target_provider_summary')
                        ->label('Target Provider')
                        ->content('Choose one validated provider candidate below.'),
                    Placeholder::make('next_invoice_reference')
                        ->label('Next Display Invoice')
                        ->content(fn (): string => $this->record->nextDisplayInvoiceId()),
                    Placeholder::make('current_status')
                        ->label('Current Status')
                        ->content(fn (): string => PembelianStatus::label($this->record->status)),
                    Placeholder::make('audit_impact')
                        ->label('Audit Impact')
                        ->content('A reset records the new provider attempt, increments the display invoice suffix, and stores the optional admin reason for audit history.'),
                    Select::make('candidate_provider_id')
                        ->label('New Provider')
                        ->options(fn (): array => $this->providerSelectOptions)
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->disabled(fn (): bool => count($this->providerSelectOptions) === 0)
                        ->helperText(fn (): string => count($this->providerSelectOptions) === 0
                            ? 'Belum ada provider path cadangan yang valid untuk layanan ini.'
                            : 'Only validated backup provider paths for this layanan are listed.'),
                    Textarea::make('reason')
                        ->label('Reason')
                        ->rows(3)
                        ->maxLength(500)
                        ->placeholder('Optional admin note for why this invoice is being reset.'),
                ])
                ->action(function (array $data, ResetDomainService $resetDomainService): void {
                    try {
                        $this->record = $resetDomainService->executeReset(
                            $this->record,
                            (int) $data['candidate_provider_id'],
                            Auth::id(),
                            $data['reason'] ?? null,
                        );

                        Notification::make()
                            ->title('Invoice reset queued successfully')
                            ->body('Display invoice aktif berubah ke ' . $this->record->display_order_id . ' dan siap dikirim ke provider baru.')
                            ->success()
                            ->send();
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('Unable to reset invoice')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('edit_reset_routing')
                ->label('Edit Reset Routing')
                ->icon('heroicon-o-pencil-square')
                ->color('info')
                ->visible(fn (): bool => $this->record->canEditResetRouting())
                ->disabled(fn (): bool => count($this->providerSelectOptions) === 0)
                ->tooltip(fn (): ?string => count($this->providerSelectOptions) === 0
                    ? 'Tidak ada provider path cadangan aktif yang bisa dipilih.'
                    : null)
                ->modalHeading('Edit Reset Routing')
                ->modalDescription(fn (): string => count($this->providerSelectOptions) === 0
                    ? 'Tidak ada provider path cadangan aktif yang bisa dipilih. Tambahkan atau aktifkan provider path di Produk Management.'
                    : 'Adjust the provider, user ID, and zone for the active reset attempt using validated candidates only.')
                ->fillForm(fn (): array => [
                    'candidate_provider_id' => null,
                    'user_id' => $this->record->user_id,
                    'zone' => $this->record->zone,
                ])
                ->form([
                    Placeholder::make('current_provider')
                        ->label('Current Provider')
                        ->content(fn (): string => $this->getCurrentProviderLabel()),
                    Placeholder::make('display_invoice')
                        ->label('Display Invoice')
                        ->content(fn (): string => $this->record->display_order_id),
                    Select::make('candidate_provider_id')
                        ->label('Provider')
                        ->options(fn (): array => $this->providerSelectOptions)
                        ->native(false)
                        ->searchable()
                        ->disabled(fn (): bool => count($this->providerSelectOptions) === 0)
                        ->placeholder('Keep current provider')
                        ->helperText(fn (): string => count($this->providerSelectOptions) === 0
                            ? 'Belum ada provider path cadangan aktif untuk layanan ini.'
                            : 'Only validated backup provider paths for this layanan are listed.'),
                    TextInput::make('user_id')
                        ->label('User ID')
                        ->maxLength(255),
                    TextInput::make('zone')
                        ->label('Zone / Server')
                        ->maxLength(255),
                ])
                ->action(function (array $data, ResetDomainService $resetDomainService): void {
                    try {
                        $this->record = $resetDomainService->updateResetDetails(
                            $this->record,
                            filled($data['candidate_provider_id'] ?? null) ? (int) $data['candidate_provider_id'] : null,
                            $data['user_id'] ?? null,
                            $data['zone'] ?? null,
                        );

                        Notification::make()
                            ->title('Reset routing updated')
                            ->body('Reset attempt sekarang memakai provider ' . $this->getCurrentProviderLabel() . '.')
                            ->success()
                            ->send();
                    } catch (DomainException $exception) {
                        Notification::make()
                            ->title('Unable to update reset routing')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('send_callback')
                ->label('Send Callback')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn (): bool => (int) $this->record->invoice_version > 0 && $this->record->canBeRetried())
                ->requiresConfirmation()
                ->modalHeading('Send Transaction To Provider')
                ->modalDescription(fn (): string => sprintf(
                    'Transaksi akan dikirim ke provider %s dengan reference %s.',
                    $this->getCurrentProviderLabel(),
                    $this->record->active_attempt_reference ?: $this->record->display_order_id ?: $this->record->order_id,
                ))
                ->action(function (OrderProcessingService $orderProcessingService): void {
                    $result = $orderProcessingService->process($this->record);

                    if (! ($result['success'] ?? false)) {
                        $this->record->update([
                            'log' => trim((string) $this->record->log) . "\n" . 'Provider dispatch failed at ' . now()->format('Y-m-d H:i:s') . ': ' . ($result['message'] ?? 'Unknown error'),
                        ]);

                        Notification::make()
                            ->title('Send callback gagal')
                            ->body($result['message'] ?? 'Unknown error')
                            ->danger()
                            ->send();

                        return;
                    }

                    $normalizedStatus = PembelianStatus::normalize($result['order_status'] ?? PembelianStatus::PENDING);
                    $nextStatus = $normalizedStatus === PembelianStatus::SUCCESS
                        ? PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS)
                        : PembelianStatus::preferredDatabaseLabel(PembelianStatus::PROCESSING);

                    $this->record->update([
                        'provider_order_id' => $result['transaction_id'] ?? $this->record->provider_order_id,
                        'status' => $nextStatus,
                        'keterangan_sn' => trim((string) ($result['sn'] ?? '')) ?: ($normalizedStatus === PembelianStatus::PENDING ? 'Sedang Diproses' : $this->record->keterangan_sn),
                        'log' => trim((string) $this->record->log) . "\n" . 'Provider dispatch at ' . now()->format('Y-m-d H:i:s') . ': ' . ($result['message'] ?? 'Order dispatched'),
                        'reset_status' => $this->record->invoice_version > 0 ? 'processing' : $this->record->reset_status,
                    ]);

                    $this->record->refresh();

                    Notification::make()
                        ->title('Transaksi berhasil dikirim ke provider')
                        ->body('Reference aktif: ' . ($this->record->active_attempt_reference ?: $this->record->display_order_id ?: $this->record->order_id))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('process_order')
                ->label('Process Order')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn ($record) => $record->hasStatus(PembelianStatus::PENDING))
                ->action(function ($record) {
                    $record->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PROCESSING)]);
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
                ->visible(fn ($record) => $record->hasStatus([PembelianStatus::PENDING, PembelianStatus::PROCESSING]))
                ->action(function ($record) {
                    $record->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::FAILED), 'log' => 'Cancelled by admin at ' . now()->format('Y-m-d H:i:s')]);
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
                ->visible(fn ($record) => $record->hasStatus(PembelianStatus::SUCCESS))
                ->action(function ($record) {
                    $record->update([
                        'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::CANCELLED),
                        'log' => 'Refund processed by admin at ' . now()->format('Y-m-d H:i:s'),
                    ]);
                    $record->syncPaymentStatusForResetEligibility();
                    Notification::make()
                        ->title('Refund processed successfully')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation(),
        ];
    }

    public function getProviderSelectOptionsProperty(): array
    {
        try {
            return app(ResetDomainService::class)
                ->getProviderSwitchCandidates($this->record)
                ->mapWithKeys(fn (ProviderPath $providerPath): array => [
                    (int) $providerPath->getKey() => sprintf(
                        '%s (%s)',
                        $this->formatProviderLabel($providerPath->provider_code),
                        trim((string) $providerPath->provider_sku),
                    ),
                ])
                ->all();
        } catch (DomainException) {
            return [];
        }
    }

    public function getCurrentProviderLabel(): string
    {
        $this->record->loadMissing('activeLayanan');

        $providerCode = trim((string) $this->record->active_provider_code);
        $providerSku = trim((string) $this->record->active_provider_sku);

        if ($providerCode !== '') {
            return $this->formatProviderLabel($providerCode) . ($providerSku !== '' ? ' (' . $providerSku . ')' : '');
        }

        $legacyProvider = trim((string) $this->record->activeLayanan?->provider);
        $legacySku = trim((string) $this->record->activeLayanan?->provider_id);

        if ($legacyProvider !== '') {
            return $this->formatProviderLabel($legacyProvider) . ($legacySku !== '' ? ' (' . $legacySku . ')' : '');
        }

        $providerPath = $this->record->activeLayanan?->provider_paths()
            ->whereIn('status', ['active', 'available'])
            ->orderBy('priority')
            ->orderBy('modal_price')
            ->first();

        if ($providerPath) {
            $pathSku = trim((string) $providerPath->provider_sku);

            return $this->formatProviderLabel($providerPath->provider_code) . ($pathSku !== '' ? ' (' . $pathSku . ')' : '');
        }

        return 'Provider context unavailable';
    }

    public function getResetStatusLabel(): string
    {
        $resetStatus = $this->record->normalizedResetStatus();

        return $resetStatus === 'none'
            ? 'Not reset'
            : (string) Str::of($resetStatus)->replace(['_', '-'], ' ')->title();
    }

    private function formatProviderLabel(?string $provider): string
    {
        $provider = trim((string) $provider);

        if ($provider === '') {
            return 'Provider context unavailable';
        }

        return (string) Str::of($provider)
            ->replace(['_', '-'], ' ')
            ->title();
    }
}
