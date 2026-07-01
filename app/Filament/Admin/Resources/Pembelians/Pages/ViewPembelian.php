<?php

namespace App\Filament\Admin\Resources\Pembelians\Pages;

use App\Filament\Admin\Resources\Pembelians\PembelianResource;
use App\Jobs\SendPembelianToProviderJob;
use App\Models\ProviderPath;
use App\Services\ResetDomainService;
use App\Support\PembelianNotificationHelper;
use App\Support\PembelianStatus;
use App\Support\ProviderDispatchTracker;
use DomainException;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
                Section::make('Informasi Order')
                    ->schema([
                        TextEntry::make('order_id')
                            ->label('Order ID')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('display_order_id')
                            ->label('Invoice Tampil')
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
                            ->label('Produk / Layanan')
                            ->default('N/A')
                            ->columnSpanFull(),
                        TextEntry::make('harga')
                            ->label('Nominal')
                            ->money('IDR')
                            ->weight('bold'),
                        TextEntry::make('current_provider')
                            ->label('Provider Aktif')
                            ->state(fn (): string => $this->getCurrentProviderLabel()),
                    ])
                    ->columns(2),

                Section::make('Detail Game')
                    ->description(fn (): string => $this->record->canEditResetRouting()
                        ? 'Reset invoice sudah dibuat. Koreksi ID game atau zone di sini sebelum klik Send Callback.'
                        : 'Detail akun game tujuan yang dikirim ke provider.')
                    ->headerActions([
                        Actions\Action::make('edit_game_details')
                            ->label('Edit ID Game / Zone')
                            ->icon('heroicon-o-pencil-square')
                            ->color('info')
                            ->visible(fn (): bool => $this->record->canEditResetRouting())
                            ->modalHeading('Edit ID Game / Zone')
                            ->modalDescription('Ubah data tujuan game untuk attempt reset ini sebelum order dikirim ulang ke provider.')
                            ->fillForm(fn (): array => [
                                'user_id' => $this->record->user_id,
                                'zone' => $this->record->zone,
                            ])
                            ->form([
                                TextInput::make('user_id')
                                    ->label('ID Game')
                                    ->helperText('ID akun game tujuan yang akan dikirim ke provider saat Send Callback.')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('zone')
                                    ->label('Zone / Server ID')
                                    ->helperText('Isi kalau game membutuhkan server/zone. Kosongkan kalau tidak perlu.')
                                    ->maxLength(255),
                            ])
                            ->action(function (array $data, ResetDomainService $resetDomainService): void {
                                try {
                                    $this->record = $resetDomainService->updateResetDetails(
                                        $this->record,
                                        null,
                                        $data['user_id'] ?? null,
                                        $data['zone'] ?? null,
                                    );

                                    Notification::make()
                                        ->title('ID Game / Zone berhasil diperbarui')
                                        ->body('Data tujuan reset sudah diperbarui. Silakan klik Send Callback jika sudah siap dikirim ke provider.')
                                        ->success()
                                        ->send();
                                } catch (DomainException $exception) {
                                    Notification::make()
                                        ->title('Gagal memperbarui ID Game / Zone')
                                        ->body($exception->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                    ])
                    ->schema([
                        TextEntry::make('user_id')
                            ->label('ID Game')
                            ->default('N/A'),
                        TextEntry::make('zone')
                            ->label('Zone / Server ID')
                            ->default('N/A'),
                        TextEntry::make('nickname')
                            ->label('Nickname Game')
                            ->default('N/A'),
                        TextEntry::make('tipe_transaksi')
                            ->label('Tipe Transaksi')
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
                        TextEntry::make('dispatch_state')
                            ->label('Dispatch State')
                            ->state(fn (): string => $this->getDispatchStateLabel())
                            ->badge()
                            ->color(fn (): string => $this->getDispatchStateBadgeColor()),
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
                        TextEntry::make('retry_status_availability')
                            ->label('Retry Status Check')
                            ->state(fn (): string => $this->record->retryUnavailableReason() ?? 'Available')
                            ->badge()
                            ->color(fn (): string => $this->record->retryUnavailableReason() ? 'warning' : 'success')
                            ->visible(fn (): bool => filled($this->record->retryUnavailableReason())),
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

                Section::make('Reseller Callback')
                    ->description('Ringkasan outbound callback live H2H untuk order ini.')
                    ->visible(fn (): bool => $this->hasResellerCallbackContext())
                    ->schema([
                        TextEntry::make('reseller_integration_code')
                            ->label('Integration Code')
                            ->state(fn (): string => $this->record->resellerIntegration?->integration_code ?: 'N/A')
                            ->copyable(),
                        TextEntry::make('reseller_callback_enabled')
                            ->label('Callback Profile')
                            ->state(fn (): string => $this->record->resellerIntegration?->callbackProfile?->is_enabled ? 'Enabled' : 'Disabled / Missing')
                            ->badge()
                            ->color(fn (): string => $this->record->resellerIntegration?->callbackProfile?->is_enabled ? 'success' : 'warning'),
                        TextEntry::make('reseller_callback_url')
                            ->label('Callback URL')
                            ->state(fn (): string => $this->latestResellerCallbackUrl())
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('reseller_callback_latest_status')
                            ->label('Latest Delivery Status')
                            ->state(fn (): string => $this->latestResellerCallbackStatus())
                            ->badge()
                            ->color(fn (): string => $this->latestResellerCallbackStatusColor()),
                        TextEntry::make('reseller_callback_last_attempted_at')
                            ->label('Last Attempted At')
                            ->state(fn (): string => optional($this->latestResellerCallbackDelivery()?->last_attempted_at)->format('d M Y H:i:s') ?: '-'),
                        TextEntry::make('reseller_callback_last_response_status')
                            ->label('HTTP Status')
                            ->state(fn (): string => (string) ($this->latestResellerCallbackDelivery()?->last_response_status ?? '-')),
                        TextEntry::make('reseller_callback_last_error')
                            ->label('Last Error')
                            ->state(fn (): string => (string) ($this->latestResellerCallbackDelivery()?->last_error ?: '-'))
                            ->columnSpanFull(),
                        TextEntry::make('reseller_callback_history')
                            ->label('Recent Delivery Log')
                            ->state(fn (): string => $this->resellerCallbackHistorySummary())
                            ->fontFamily('mono')
                            ->wrap()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Informasi Sistem')
                    ->schema([
                        TextEntry::make('keterangan_sn')
                            ->label('SN / Keterangan')
                            ->state(fn (): string => $this->record->keterangan_sn ?: ($this->record->voucher ?: 'N/A'))
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('log')
                            ->label('Log Sistem')
                            ->default('N/A')
                            ->fontFamily('mono')
                            ->wrap()
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Dibuat')
                            ->dateTime('d M Y H:i:s'),
                        TextEntry::make('updated_at')
                            ->label('Terakhir Update')
                            ->dateTime('d M Y H:i:s'),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send_notification')
                ->label('Send Notification')
                ->icon('heroicon-o-paper-airplane')
                ->color('secondary')
                ->disabled(fn (): bool => empty(PembelianNotificationHelper::channelOptions($this->record)))
                ->tooltip(fn (): ?string => empty(PembelianNotificationHelper::channelOptions($this->record))
                    ? 'Order ini tidak memiliki nomor WhatsApp maupun email yang bisa dipakai untuk mengirim notifikasi.'
                    : null)
                ->fillForm(fn (): array => [
                    'channel' => array_key_first(PembelianNotificationHelper::channelOptions($this->record)),
                ])
                ->form([
                    TextEntry::make('detected_whatsapp')
                        ->label('WhatsApp Target')
                        ->state(fn (): string => PembelianNotificationHelper::whatsappTarget($this->record) ?? 'Tidak tersedia'),
                    TextEntry::make('detected_email')
                        ->label('Email Target')
                        ->state(fn (): string => PembelianNotificationHelper::emailTarget($this->record) ?? 'Tidak tersedia'),
                    TextEntry::make('availability_note')
                        ->label('Availability')
                        ->state(fn (): string => PembelianNotificationHelper::availabilityMessage($this->record)),
                    Select::make('channel')
                        ->label('Send via')
                        ->options(fn (): array => PembelianNotificationHelper::channelOptions($this->record))
                        ->default(fn (): ?string => array_key_first(PembelianNotificationHelper::channelOptions($this->record)))
                        ->native(false)
                        ->required()
                        ->helperText('Hanya channel yang memiliki target kontak valid yang ditampilkan.'),
                ])
                ->action(function (array $data): void {
                    $channel = (string) ($data['channel'] ?? '');
                    $availableChannels = array_keys(PembelianNotificationHelper::channelOptions($this->record));

                    if (! in_array($channel, $availableChannels, true)) {
                        Notification::make()
                            ->title('Channel tidak tersedia')
                            ->body('Pilih channel notifikasi yang memiliki target kontak valid untuk order ini.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $results = PembelianNotificationHelper::send($this->record, $channel);

                    Notification::make()
                        ->title('Notification Processed')
                        ->body(implode(', ', $results))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('reset_invoice')
                ->label('Reset Invoice')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => $this->record->isResetEligible())
                ->requiresConfirmation()
                ->modalHeading('Reset Invoice')
                ->modalDescription(fn (): string => count($this->providerSelectOptions) === 0
                    ? 'Buat percobaan ulang untuk order ini. Order akan dikirim ulang ke provider saat ini.'
                    : 'Buat percobaan ulang untuk order ini. Provider baru opsional, kosongkan jika tetap memakai provider saat ini.')
                ->form([
                    TextEntry::make('current_provider')
                        ->label('Provider Saat Ini')
                        ->state(fn (): string => $this->getCurrentProviderLabel()),
                    TextEntry::make('target_provider_summary')
                        ->label('Provider Tujuan')
                        ->state(fn (): string => count($this->providerSelectOptions) === 0
                            ? 'Tetap memakai provider saat ini.'
                            : 'Opsional. Pilih provider baru jika ingin pindah jalur.'),
                    TextEntry::make('next_invoice_reference')
                        ->label('Invoice Baru')
                        ->state(fn (): string => $this->record->nextDisplayInvoiceId()),
                    TextEntry::make('current_status')
                        ->label('Status Saat Ini')
                        ->state(fn (): string => PembelianStatus::label($this->record->status)),
                    Select::make('candidate_provider_id')
                        ->label('Provider Baru (Opsional)')
                        ->options(fn (): array => $this->providerSelectOptions)
                        ->native(false)
                        ->searchable()
                        ->placeholder('Tetap pakai provider saat ini')
                        ->helperText(fn (): string => count($this->providerSelectOptions) === 0
                            ? 'Tidak ada provider cadangan aktif. Sistem akan memakai provider saat ini.'
                            : 'Kosongkan jika ingin tetap memakai provider saat ini.'),
                    Textarea::make('reason')
                        ->label('Catatan Admin (Opsional)')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Catatan ini hanya untuk riwayat internal admin.')
                        ->placeholder('Contoh: ID salah, provider gagal, atau diminta retry oleh client.'),
                ])
                ->action(function (array $data, ResetDomainService $resetDomainService): void {
                    try {
                        $candidateProviderId = filled($data['candidate_provider_id'] ?? null)
                            ? (int) $data['candidate_provider_id']
                            : null;

                        $this->record = $resetDomainService->executeReset(
                            $this->record,
                            $candidateProviderId,
                            Auth::id(),
                            $data['reason'] ?? null,
                        );

                        Notification::make()
                            ->title('Invoice reset queued successfully')
                            ->body('Display invoice aktif berubah ke ' . $this->record->display_order_id . ' dan siap dikirim ke provider ' . $this->getCurrentProviderLabel() . '.')
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
                    TextEntry::make('current_provider')
                        ->label('Current Provider')
                        ->state(fn (): string => $this->getCurrentProviderLabel()),
                    TextEntry::make('display_invoice')
                        ->label('Display Invoice')
                        ->state(fn (): string => $this->record->display_order_id),
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
                ->disabled(fn (): bool => ProviderDispatchTracker::isActive($this->record->getKey()))
                ->tooltip(fn (): ?string => ProviderDispatchTracker::isActive($this->record->getKey())
                    ? 'Dispatch masih dalam antrean/proses. Tunggu sebentar lalu refresh.'
                    : null)
                ->requiresConfirmation()
                ->modalHeading('Send Transaction To Provider')
                ->modalDescription(fn (): string => sprintf(
                    'Transaksi akan dikirim ke provider %s dengan reference %s.',
                    $this->getCurrentProviderLabel(),
                    $this->record->active_attempt_reference ?: $this->record->display_order_id ?: $this->record->order_id,
                ))
                ->action(function (): void {
                    if (ProviderDispatchTracker::isActive($this->record->getKey())) {
                        Notification::make()
                            ->title('Dispatch masih berjalan')
                            ->body('Order ini masih dalam antrean/proses provider. Tunggu sebentar lalu refresh.')
                            ->warning()
                            ->send();

                        return;
                    }

                    try {
                        $this->record->update([
                            'log' => $this->appendBoundedLog(
                                $this->record->log,
                                'Provider dispatch queued at ' . now()->format('Y-m-d H:i:s') . ' by admin.',
                            ),
                            'reset_status' => $this->record->invoice_version > 0 ? 'processing' : $this->record->reset_status,
                        ]);

                        SendPembelianToProviderJob::dispatch($this->record->getKey(), Auth::id());
                        ProviderDispatchTracker::markQueued($this->record->getKey());

                        $this->record->refresh();

                        Notification::make()
                            ->title('Send callback masuk antrean')
                            ->body('Reference aktif: ' . ($this->record->active_attempt_reference ?: $this->record->display_order_id ?: $this->record->order_id))
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        ProviderDispatchTracker::clear($this->record->getKey());

                        Log::error('Queueing send callback action failed.', [
                            'order_id' => $this->record->order_id,
                            'display_order_id' => $this->record->display_order_id,
                            'active_attempt_reference' => $this->record->active_attempt_reference,
                            'active_provider_code' => $this->record->active_provider_code,
                            'active_provider_sku' => $this->record->active_provider_sku,
                            'message' => $exception->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Send callback gagal')
                            ->body('Job tidak berhasil dimasukkan ke antrean. Cek log aplikasi untuk detailnya.')
                            ->danger()
                            ->send();
                    }
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

    public function getDispatchStateLabel(): string
    {
        return ProviderDispatchTracker::label($this->record->getKey());
    }

    public function getDispatchStateBadgeColor(): string
    {
        return ProviderDispatchTracker::badgeColor($this->record->getKey());
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

    private function appendBoundedLog(?string $existingLog, string $entry, int $limit = 1000): string
    {
        $existingLog = trim((string) $existingLog);
        $entry = trim($entry);

        $combined = $existingLog !== ''
            ? $existingLog . PHP_EOL . $entry
            : $entry;

        if (mb_strlen($combined) <= $limit) {
            return $combined;
        }

        return mb_substr($combined, -$limit);
    }

    private function hasResellerCallbackContext(): bool
    {
        $this->record->loadMissing(['resellerIntegration.callbackProfile', 'resellerCallbackDeliveries']);

        return $this->record->reseller_integration_id !== null
            || $this->record->resellerCallbackDeliveries->isNotEmpty();
    }

    private function latestResellerCallbackDelivery()
    {
        $this->record->loadMissing(['resellerCallbackDeliveries']);

        return $this->record->resellerCallbackDeliveries->first();
    }

    private function latestResellerCallbackStatus(): string
    {
        return (string) ($this->latestResellerCallbackDelivery()?->status ?: 'No deliveries');
    }

    private function latestResellerCallbackStatusColor(): string
    {
        return match ($this->latestResellerCallbackStatus()) {
            'delivered' => 'success',
            'failed' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };
    }

    private function latestResellerCallbackUrl(): string
    {
        $this->record->loadMissing(['resellerIntegration.callbackProfile']);

        return (string) (
            $this->latestResellerCallbackDelivery()?->callback_url
            ?: $this->record->resellerIntegration?->callbackProfile?->callback_url
            ?: 'N/A'
        );
    }

    private function resellerCallbackHistorySummary(): string
    {
        $this->record->loadMissing(['resellerCallbackDeliveries']);

        if ($this->record->resellerCallbackDeliveries->isEmpty()) {
            return 'Belum ada delivery log outbound.';
        }

        return $this->record->resellerCallbackDeliveries
            ->take(5)
            ->map(function ($delivery): string {
                $attemptedAt = optional($delivery->last_attempted_at)->format('Y-m-d H:i:s') ?: '-';
                $statusCode = $delivery->last_response_status !== null ? (string) $delivery->last_response_status : '-';
                $error = trim((string) ($delivery->last_error ?? ''));

                return sprintf(
                    '[%s] %s event=%s http=%s error=%s',
                    $attemptedAt,
                    $delivery->status,
                    $delivery->event_name,
                    $statusCode,
                    $error !== '' ? $error : '-',
                );
            })
            ->implode(PHP_EOL);
    }

}

