<?php

namespace App\Filament\Admin\Resources\Pembelians\Tables;

use App\Jobs\SendPembelianToProviderJob;
use App\Support\PembelianNotificationHelper;
use App\Support\PaymentStatus;
use App\Support\PembelianStatus;
use App\Support\ProviderDispatchTracker;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PembeliansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_order_id')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn($record) => $record->display_order_id !== $record->order_id
                        ? 'Canonical: ' . $record->order_id
                        : null)
                    ->weight('bold'),

                TextColumn::make('nickname')
                    ->label('Akun Game')
                    ->getStateUsing(fn ($record) => self::gameAccountLabel($record))
                    ->description(fn($record) => $record->nickname ?? '-')
                    ->searchable(['user_id', 'nickname', 'zone'])
                    ->sortable(),

                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->default('Anonim')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('layanan')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->description(function ($record) {
                        $source = $record->traffic_source ?? 'Original';
                        $label = self::trafficSourceLabel($source);
                        $icon = self::trafficSourceIconSvg($source);

                        return new \Illuminate\Support\HtmlString(
                            "<span class='inline-flex items-center gap-1.5 rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-200'>{$icon}<span>{$label}</span></span>"
                        );
                    }),

                TextColumn::make('status')
                    ->label('Status Provider')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->status_display_label)
                    ->color(fn($record) => $record->status_badge_color)
                    ->icon(fn($record) => $record->status_icon)
                    ->sortable(),

                TextColumn::make('dispatch_state')
                    ->label('Status Pengiriman')
                    ->badge()
                    ->getStateUsing(fn($record): string => self::dispatchStateLabel($record))
                    ->color(fn($record): string => self::dispatchStateBadgeColor($record))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('pembayaran.status')
                    ->label('Status Pembayaran')
                    ->badge()
                    ->getStateUsing(fn ($record): string => PaymentStatus::label(optional($record->pembayaran)->status))
                    ->color(fn ($record): string => PaymentStatus::badgeColor(optional($record->pembayaran)->status))
                    ->icon(fn ($record): string => PaymentStatus::icon(optional($record->pembayaran)->status)),

                TextColumn::make('pembayaran.no_pembeli')
                    ->label('No. WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->default('-'),

                TextColumn::make('keterangan_sn')
                    ->label('SN / Keterangan')
                    ->default('-')
                    ->wrap()
                    ->limit(50),

                TextColumn::make('used_points')
                    ->label('Poin Digunakan')
                    ->getStateUsing(fn ($record) => self::usedPointsLabel($record))
                    ->description(fn ($record) => self::usedPointsDescription($record))
                    ->wrap()
                    ->toggleable(),



                TextColumn::make('harga')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                TextColumn::make('pembayaran.metode')
                    ->label('Metode')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->getStateUsing(fn ($record) => self::paymentMethodLabel($record))
                    ->toggleable(),

                TextColumn::make('profit')
                    ->label('Profit')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('zone')
                    ->label('Zone/Server')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('N/A'),

                TextColumn::make('tipe_transaksi')
                    ->label('Tipe')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tanggal Order')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Provider')
                    ->options(PembelianStatus::filterOptions())
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        $values = (array) ($data['values'] ?? []);

                        if ($values === []) {
                            return $query;
                        }

                        $rawStatuses = collect($values)
                            ->flatMap(static fn(string $status) => PembelianStatus::aliasesFor($status))
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        if ($rawStatuses === []) {
                            return $query;
                        }

                        return $query->whereIn('status', $rawStatuses);
                    }),

                SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options(self::paymentStatusOptions())
                    ->multiple()
                    ->query(fn (Builder $query, array $data): Builder => self::applyPaymentStatusFilter($query, $data)),

                SelectFilter::make('tipe_transaksi')
                    ->label('Tipe Transaksi')
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
                            ->label('Dari Tanggal'),
                        DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->where(
                                    'created_at',
                                    '>=',
                                    \Carbon\Carbon::parse($date)->startOfDay(),
                                ),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->where(
                                    'created_at',
                                    '<=',
                                    \Carbon\Carbon::parse($date)->endOfDay(),
                                ),
                            );
                    }),

                Filter::make('amount_range')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('amount_from')
                            ->label('Nominal Minimum')
                            ->numeric(),
                        \Filament\Forms\Components\TextInput::make('amount_until')
                            ->label('Nominal Maksimum')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn(Builder $query, $amount): Builder => $query->where('harga', '>=', $amount),
                            )
                            ->when(
                                $data['amount_until'],
                                fn(Builder $query, $amount): Builder => $query->where('harga', '<=', $amount),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat Detail'),

                    Action::make('editStatus')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-pencil-square')
                        ->color('primary')
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->label('Status Baru')
                                ->options(PembelianStatus::manualStatusOptions())
                                ->required()
                                ->default(fn($record) => $record->status),
                        ])
                        ->action(function ($record, array $data) {
                            $oldStatus = $record->status;
                            $newStatus = $data['status'];

                            if ($oldStatus === $newStatus)
                                return;

                            $logMsg = $record->log . "\nStatus diubah manual oleh admin dari '{$oldStatus}' menjadi '{$newStatus}' pada " . now()->format('Y-m-d H:i:s');
                            $record->update(['status' => $newStatus, 'log' => $logMsg]);
                            $record->syncPaymentStatusForResetEligibility($newStatus);

                            Notification::make()
                                ->title('Status berhasil diubah')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ubah Status Pembelian')
                        ->modalDescription('Apakah Anda yakin ingin mengubah status pembelian ini? Pastikan Anda sudah mengecek mutasi atau dashboard provider terkait.')
                        ->modalSubmitActionLabel('Ya, Ubah Status'),

                    Action::make('process')
                        ->label('Proses')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->visible(fn($record) => $record->hasStatus(PembelianStatus::PENDING))
                        ->action(function ($record) {
                            $record->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PROCESSING)]);
                            Notification::make()
                                ->title('Order berhasil diproses')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->visible(fn($record) => $record->hasStatus([PembelianStatus::PENDING, PembelianStatus::PROCESSING]))
                        ->action(function ($record) {
                            $logMsg = 'Cancelled by admin at ' . now()->format('Y-m-d H:i:s');

                            // Refund logic if SALDO
                            if ($record->pembayaran && $record->pembayaran->metode === 'SALDO' && $record->user) {
                                $record->user->increment('balance', $record->harga);
                                $logMsg .= " (Saldo Rp " . number_format($record->harga, 0, ',', '.') . " di-refund)";
                            }

                            app(\App\Services\PointService::class)->refundRedeemedPoints($record);

                            $record->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::FAILED), 'log' => $logMsg]);
                            Notification::make()
                                ->title('Order dibatalkan')
                                ->body('Saldo/poin dikembalikan jika memenuhi syarat.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Action::make('refund')
                        ->label('Refund Saldo Deposit')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn($record) => $record->hasStatus(PembelianStatus::SUCCESS))
                        ->action(function ($record) {
                            $logMsg = 'Refund processed by admin at ' . now()->format('Y-m-d H:i:s');

                            // Refund logic if SALDO
                            if ($record->pembayaran && $record->pembayaran->metode === 'SALDO' && $record->user) {
                                $record->user->increment('balance', $record->harga);
                                $logMsg .= " (Saldo Rp " . number_format($record->harga, 0, ',', '.') . " di-refund)";
                            }

                            app(\App\Services\PointService::class)->refundRedeemedPoints($record);

                            $record->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::CANCELLED), 'log' => $logMsg]);
                            $record->syncPaymentStatusForResetEligibility();
                            Notification::make()
                                ->title('Refund processed successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Action::make('retry')
                        ->label('Retry Order')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->visible(fn($record) => $record->canBeRetried())
                        ->disabled(fn($record) => !$record->canRunRetryStatusCheck())
                        ->tooltip(fn($record): ?string => $record->retryUnavailableReason())
                        ->action(function ($record) {
                            if (!$record->canRunRetryStatusCheck()) {
                                Notification::make()
                                    ->title('Retry status belum bisa dijalankan')
                                    ->body($record->retryUnavailableReason() ?? 'Retry status tidak tersedia untuk transaksi ini.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            if (ProviderDispatchTracker::isActive($record->getKey())) {
                                Notification::make()
                                    ->title('Retry sedang berjalan')
                                    ->body('Order ini masih dalam antrean/proses provider. Tunggu sebentar lalu refresh.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            try {
                                $record->update([
                                    'log' => self::appendBoundedLog(
                                        $record->log,
                                        'Retry queued by admin at ' . now()->format('Y-m-d H:i:s'),
                                    ),
                                ]);

                                SendPembelianToProviderJob::dispatch($record->getKey(), Auth::id(), 'retry_status');
                                ProviderDispatchTracker::markQueued($record->getKey());

                                Notification::make()
                                    ->title('Retry masuk antrean')
                                    ->body('Order dikirim ke queue agar tetap responsif saat trafik tinggi.')
                                    ->success()
                                    ->send();
                            } catch (\Throwable $exception) {
                                ProviderDispatchTracker::clear($record->getKey());

                                Log::error('Retry order dispatch failed.', [
                                    'pembelian_id' => $record->getKey(),
                                    'order_id' => $record->order_id,
                                    'display_order_id' => $record->display_order_id,
                                    'message' => $exception->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Retry gagal diproses')
                                    ->body('Job gagal masuk antrean. Cek log aplikasi.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Retry Transaction?')
                        ->modalDescription('Are you sure you want to retry this transaction? This will attempt to send the order to the provider again.'),

                    Action::make('resend_notification')
                        ->label('Resend Notif')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('secondary')
                        ->disabled(function ($record): bool {
                            $targetWa = PembelianNotificationHelper::whatsappTarget($record);
                            $targetEmail = PembelianNotificationHelper::emailTarget($record);

                            return $targetWa === null && $targetEmail === null;
                        })
                        ->tooltip(function ($record): ?string {
                            $targetWa = PembelianNotificationHelper::whatsappTarget($record);
                            $targetEmail = PembelianNotificationHelper::emailTarget($record);

                            return $targetWa === null && $targetEmail === null
                                ? 'Order ini tidak memiliki nomor WhatsApp maupun email yang bisa dipakai untuk mengirim notifikasi.'
                                : null;
                        })
                        ->fillForm(function ($record): array {
                            return [
                                'channel' => array_key_first(PembelianNotificationHelper::channelOptions($record)),
                            ];
                        })
                        ->form(function ($record): array {
                            return [
                                \Filament\Forms\Components\Placeholder::make('detected_whatsapp')
                                    ->label('WhatsApp Target')
                                    ->content(PembelianNotificationHelper::whatsappTarget($record) ?? 'Tidak tersedia'),
                                \Filament\Forms\Components\Placeholder::make('detected_email')
                                    ->label('Email Target')
                                    ->content(PembelianNotificationHelper::emailTarget($record) ?? 'Tidak tersedia'),
                                \Filament\Forms\Components\Placeholder::make('availability_note')
                                    ->label('Availability')
                                    ->content(PembelianNotificationHelper::availabilityMessage($record)),
                                \Filament\Forms\Components\Select::make('channel')
                                    ->label('Send via')
                                    ->options(PembelianNotificationHelper::channelOptions($record))
                                    ->default(array_key_first(PembelianNotificationHelper::channelOptions($record)))
                                    ->native(false)
                                    ->required()
                                    ->helperText('Hanya channel yang memiliki target kontak valid yang ditampilkan.'),
                            ];
                        })
                        ->action(function ($record, array $data) {
                            $channel = (string) ($data['channel'] ?? '');
                            $availableChannels = array_keys(PembelianNotificationHelper::channelOptions($record));

                            if (! in_array($channel, $availableChannels, true)) {
                                Notification::make()
                                    ->title('Channel tidak tersedia')
                                    ->body('Pilih channel notifikasi yang memiliki target kontak valid untuk order ini.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $results = PembelianNotificationHelper::send($record, $channel);

                            Notification::make()
                                ->title('Notifikasi diproses')
                                ->body(implode(', ', $results))
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])

            ->headerActions([
                ExportAction::make('export_sales_report')
                    ->label('Export Sales Report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exports([
                        ExcelExport::make('sales_report')
                            ->askForFilename()
                            ->askForWriterType()
                            ->withFilename(fn() => 'laporan-sales-' . now()->format('Y-m-d'))
                            ->withColumns(self::salesReportExportColumns()),
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Data (Laporan Sales)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->exports([
                            ExcelExport::make('sales_report')
                                ->askForFilename()
                                ->askForWriterType()
                                ->withFilename(fn() => 'laporan-sales-' . now()->format('Y-m-d'))
                                ->withColumns(self::salesReportExportColumns()),
                        ]),
                    BulkAction::make('bulk_process')
                        ->label('Process Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->filter(fn($record) => $record->hasStatus(PembelianStatus::PENDING))->count();
                            $records->filter(fn($record) => $record->hasStatus(PembelianStatus::PENDING))->each(function ($record) {
                                $record->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PROCESSING)]);
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
                            $count = $records->filter(fn($record) => $record->hasStatus([PembelianStatus::PENDING, PembelianStatus::PROCESSING]))->count();
                            $records->filter(fn($record) => $record->hasStatus([PembelianStatus::PENDING, PembelianStatus::PROCESSING]))->each(function ($record) {
                                $record->update(['status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::FAILED), 'log' => 'Bulk cancelled by admin at ' . now()->format('Y-m-d H:i:s')]);
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
            ->searchDebounce('800ms')
            ->persistSearchInSession()
            ->defaultPaginationPageOption(25)
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    private static function salesReportExportColumns(): array
    {
        return [
            Column::make('display_order_id')
                ->heading('INVOICE')
                ->getStateUsing(fn($record) => $record->display_order_id ?: $record->order_id),

            Column::make('created_at')
                ->heading('TANGGAL')
                ->getStateUsing(fn($record) => $record->created_at?->format('d/m/Y H:i')),

            Column::make('status')
                ->heading('STATUS PROVIDER')
                ->getStateUsing(fn($record) => $record->status_display_label ?? $record->status),

            Column::make('pembayaran.status')
                ->heading('STATUS PEMBAYARAN')
                ->getStateUsing(fn($record) => $record->pembayaran?->status),

            Column::make('pembayaran.metode')
                ->heading('METODE PEMBAYARAN')
                ->getStateUsing(fn($record) => $record->pembayaran?->metode),

            Column::make('username')
                ->heading('USERNAME')
                ->getStateUsing(fn($record) => $record->username),

            Column::make('whatsapp')
                ->heading('WHATSAPP')
                ->getStateUsing(fn($record) => $record->pembayaran?->no_pembeli ?? $record->user?->no_wa),

            Column::make('email')
                ->heading('EMAIL')
                ->getStateUsing(fn($record) => $record->email_pembeli ?? $record->user?->email),

            Column::make('layanan')
                ->heading('PRODUK')
                ->getStateUsing(fn($record) => $record->layanan),

            Column::make('tipe_transaksi')
                ->heading('TIPE TRANSAKSI')
                ->getStateUsing(fn($record) => $record->tipe_transaksi),

            Column::make('user_id')
                ->heading('GAME ID')
                ->getStateUsing(fn($record) => $record->user_id),

            Column::make('zone')
                ->heading('ZONE')
                ->getStateUsing(fn($record) => $record->zone),

            Column::make('nickname')
                ->heading('NICKNAME')
                ->getStateUsing(fn($record) => $record->nickname),

            Column::make('harga')
                ->heading('HARGA JUAL')
                ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                ->getStateUsing(fn($record) => $record->harga),

            Column::make('profit')
                ->heading('PROFIT')
                ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                ->getStateUsing(fn($record) => $record->profit),

            Column::make('used_points')
                ->heading('USED POINTS')
                ->getStateUsing(fn($record) => $record->used_points),

            Column::make('used_point_amount')
                ->heading('USED POINT AMOUNT')
                ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                ->getStateUsing(fn($record) => $record->used_point_amount),

            Column::make('traffic_source')
                ->heading('TRAFFIC SOURCE')
                ->getStateUsing(fn($record) => $record->traffic_source),

            Column::make('provider_order_id')
                ->heading('PROVIDER TRX ID')
                ->getStateUsing(fn($record) => $record->provider_order_id),

            Column::make('sn')
                ->heading('SN/KETERANGAN')
                ->getStateUsing(fn($record) => $record->keterangan_sn ?: $record->voucher),
        ];
    }

    private static function appendBoundedLog(?string $existingLog, string $entry, int $limit = 1000): string
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

    private static function dispatchTrackerState($record): ?array
    {
        static $stateCache = [];

        $recordKey = (string) $record->getKey();

        if (! array_key_exists($recordKey, $stateCache)) {
            $stateCache[$recordKey] = ProviderDispatchTracker::getState($record->getKey());
        }

        return $stateCache[$recordKey];
    }

    private static function dispatchStateLabel($record): string
    {
        $state = self::dispatchTrackerState($record)['state'] ?? null;

        return match ($state) {
            'queued' => 'Queued',
            'processing' => 'Processing',
            default => 'Idle',
        };
    }

    private static function dispatchStateBadgeColor($record): string
    {
        $state = self::dispatchTrackerState($record)['state'] ?? null;

        return match ($state) {
            'queued' => 'warning',
            'processing' => 'info',
            default => 'gray',
        };
    }

    private static function gameAccountLabel($record): string
    {
        $userId = $record->user_id ?? '-';
        $zone = trim((string) ($record->zone ?? ''));

        if ($zone !== '' && $zone !== 'N/A') {
            return $userId . ' (' . $zone . ')';
        }

        return $userId;
    }

    private static function usedPointsLabel($record): string
    {
        $usedPoints = (int) ($record->used_points ?? 0);

        if ($usedPoints <= 0) {
            return '-';
        }

        return number_format($usedPoints, 0, ',', '.') . ' poin';
    }

    private static function usedPointsDescription($record): ?string
    {
        $usedPoints = (int) ($record->used_points ?? 0);
        $usedPointAmount = (int) ($record->used_point_amount ?? 0);

        if ($usedPoints <= 0 || $usedPointAmount <= 0) {
            return null;
        }

        $hargaBayar = (int) ($record->harga ?? 0);
        $hargaSebelumPoin = $hargaBayar + $usedPointAmount;

        return 'Rp ' . number_format($usedPointAmount, 0, ',', '.') .
            ' (Rp ' . number_format($hargaSebelumPoin, 0, ',', '.') .
            ' - Rp ' . number_format($usedPointAmount, 0, ',', '.') .
            ' = Rp ' . number_format($hargaBayar, 0, ',', '.') . ')';
    }

    private static function cachedPaymentMethodMap(): array
    {
        static $methodCache = null;

        if ($methodCache !== null) {
            return $methodCache;
        }

        return $methodCache = \Illuminate\Support\Facades\Cache::remember('admin:pembelians:method-map', now()->addMinutes(15), function (): array {
            return \App\Models\Method::query()
                ->pluck('payment', 'code')
                ->toArray();
        });
    }

    private static function paymentMethodLabel($record): string
    {
        $metode = optional($record->pembayaran)->metode;

        if (! $metode) {
            return '-';
        }

        if (strtoupper($metode) === 'SALDO') {
            return 'SALDO';
        }

        $provider = self::cachedPaymentMethodMap()[$metode] ?? null;

        return $provider ? $provider . '.' . strtolower($metode) : $metode;
    }

    private static function paymentStatusOptions(): array
    {
        return PaymentStatus::options();
    }

    private static function applyPaymentStatusFilter(Builder $query, array $data): Builder
    {
        return PaymentStatus::applyPembelianQuery($query, (array) ($data['values'] ?? []));
    }

    private static function trafficSourceLabel(?string $source): string
    {
        $source = trim((string) ($source ?: 'Original'));

        if ($source === '') {
            return 'Original';
        }

        return match (strtolower($source)) {
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            'youtube' => 'YouTube',
            'google' => 'Google',
            'whatsapp' => 'WhatsApp',
            'direct' => 'Direct',
            'original' => 'Original',
            default => e($source),
        };
    }

    private static function trafficSourceIconSvg(?string $source): string
    {
        return match (strtolower(trim((string) $source))) {
            'facebook' => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#1877F2" d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.03 1.79-4.7 4.53-4.7 1.31 0 2.68.23 2.68.23v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.27h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07Z"/></svg>',
            'instagram' => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><defs><linearGradient id="ig-source-gradient" x1="0" x2="1" y1="1" y2="0"><stop offset="0" stop-color="#FEDA75"/><stop offset=".35" stop-color="#FA7E1E"/><stop offset=".65" stop-color="#D62976"/><stop offset="1" stop-color="#4F5BD5"/></linearGradient></defs><rect width="24" height="24" rx="6" fill="url(#ig-source-gradient)"/><path fill="#fff" d="M12 7.1a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8Zm0 8.08a3.18 3.18 0 1 1 0-6.36 3.18 3.18 0 0 1 0 6.36Zm6.25-8.28a1.14 1.14 0 1 1-2.28 0 1.14 1.14 0 0 1 2.28 0Z"/><path fill="#fff" d="M12 3.6c2.28 0 2.55.01 3.45.05.83.04 1.28.18 1.58.3.4.15.68.34.98.64.3.3.49.58.64.98.12.3.26.75.3 1.58.04.9.05 1.17.05 3.45s-.01 2.55-.05 3.45c-.04.83-.18 1.28-.3 1.58-.15.4-.34.68-.64.98-.3.3-.58.49-.98.64-.3.12-.75.26-1.58.3-.9.04-1.17.05-3.45.05s-2.55-.01-3.45-.05c-.83-.04-1.28-.18-1.58-.3a2.64 2.64 0 0 1-.98-.64 2.64 2.64 0 0 1-.64-.98c-.12-.3-.26-.75-.3-1.58C5.01 13.15 5 12.88 5 10.6s.01-2.55.05-3.45c.04-.83.18-1.28.3-1.58.15-.4.34-.68.64-.98.3-.3.58-.49.98-.64.3-.12.75-.26 1.58-.3.9-.04 1.17-.05 3.45-.05Zm0-1.54c-2.32 0-2.61.01-3.52.05-.91.04-1.53.19-2.07.4-.56.22-1.04.51-1.51.98-.47.47-.76.95-.98 1.51-.21.54-.36 1.16-.4 2.07-.04.91-.05 1.2-.05 3.52s.01 2.61.05 3.52c.04.91.19 1.53.4 2.07.22.56.51 1.04.98 1.51.47.47.95.76 1.51.98.54.21 1.16.36 2.07.4.91.04 1.2.05 3.52.05s2.61-.01 3.52-.05c.91-.04 1.53-.19 2.07-.4.56-.22 1.04-.51 1.51-.98.47-.47.76-.95.98-1.51.21-.54.36-1.16.4-2.07.04-.91.05-1.2.05-3.52s-.01-2.61-.05-3.52c-.04-.91-.19-1.53-.4-2.07a4.18 4.18 0 0 0-.98-1.51 4.18 4.18 0 0 0-1.51-.98c-.54-.21-1.16-.36-2.07-.4-.91-.04-1.2-.05-3.52-.05Z"/></svg>',
            'tiktok' => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#111827" d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.3 0 .6.05.88.14V9.4a6.34 6.34 0 0 0-.88-.06A6.33 6.33 0 0 0 5 20.14a6.34 6.34 0 0 0 10.86-4.43V8.78a8.21 8.21 0 0 0 4.8 1.54V6.88c-.36 0-.72-.06-1.07-.19Z"/></svg>',
            'youtube' => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#FF0000" d="M23.5 6.2a3 3 0 0 0-2.1-2.13C19.55 3.56 12 3.56 12 3.56s-7.55 0-9.4.5A3 3 0 0 0 .5 6.2 31.2 31.2 0 0 0 0 12a31.2 31.2 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.13c1.85.5 9.4.5 9.4.5s7.55 0 9.4-.5a3 3 0 0 0 2.1-2.13A31.2 31.2 0 0 0 24 12a31.2 31.2 0 0 0-.5-5.8Z"/><path fill="#fff" d="M9.75 15.57V8.43L16 12l-6.25 3.57Z"/></svg>',
            'google' => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.2-2.27H12v4.51h6.47a5.54 5.54 0 0 1-2.4 3.64v2.98h3.89c2.27-2.09 3.53-5.17 3.53-8.86Z"/><path fill="#34A853" d="M12 24c3.24 0 5.96-1.07 7.95-2.9l-3.89-2.98c-1.08.72-2.45 1.15-4.06 1.15-3.12 0-5.77-2.11-6.72-4.96H1.27v3.07A12 12 0 0 0 12 24Z"/><path fill="#FBBC05" d="M5.28 14.31a7.2 7.2 0 0 1 0-4.62V6.62H1.27a12 12 0 0 0 0 10.76l4.01-3.07Z"/><path fill="#EA4335" d="M12 4.73c1.76 0 3.35.61 4.6 1.8l3.44-3.44A11.55 11.55 0 0 0 12 0 12 12 0 0 0 1.27 6.62l4.01 3.07C6.23 6.84 8.88 4.73 12 4.73Z"/></svg>',
            'whatsapp' => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#25D366" d="M20.52 3.48A11.79 11.79 0 0 0 12.1 0C5.55 0 .23 5.32.23 11.86c0 2.09.55 4.13 1.58 5.93L.13 24l6.36-1.67a11.88 11.88 0 0 0 5.61 1.43h.01c6.54 0 11.86-5.32 11.86-11.86 0-3.17-1.23-6.15-3.45-8.42Z"/><path fill="#fff" d="M12.1 21.75h-.01a9.84 9.84 0 0 1-5.02-1.38l-.36-.22-3.77.99 1-3.67-.24-.38a9.82 9.82 0 0 1-1.51-5.23c0-5.45 4.44-9.89 9.91-9.89 2.65 0 5.13 1.03 7 2.9a9.82 9.82 0 0 1 2.9 7.02c0 5.46-4.44 9.86-9.9 9.86Zm5.43-7.39c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.08-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35Z"/></svg>',
            'direct' => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#64748B" d="M3.9 12a5 5 0 0 1 5-5h4v2h-4a3 3 0 1 0 0 6h4v2h-4a5 5 0 0 1-5-5Zm6.1 1h4v-2h-4v2Zm1-4h4a3 3 0 1 1 0 6h-4v2h4a5 5 0 1 0 0-10h-4v2Z"/></svg>',
            default => '<svg style="width:12px;height:12px;display:inline-block;flex-shrink:0" viewBox="0 0 24 24" aria-hidden="true"><path fill="#64748B" d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm6.93 6h-2.95a15.7 15.7 0 0 0-1.38-3.01A8.04 8.04 0 0 1 18.93 8ZM12 4.04c.83 1.2 1.48 2.54 1.9 3.96h-3.8A13.7 13.7 0 0 1 12 4.04ZM4.26 14a8.4 8.4 0 0 1 0-4h3.33a16.8 16.8 0 0 0 0 4H4.26Zm.81 2h2.95c.34 1.07.8 2.08 1.38 3.01A8.04 8.04 0 0 1 5.07 16Zm2.95-8H5.07A8.04 8.04 0 0 1 9.4 4.99 15.7 15.7 0 0 0 8.02 8ZM12 19.96A13.7 13.7 0 0 1 10.1 16h3.8a13.7 13.7 0 0 1-1.9 3.96ZM14.33 14H9.67a14.7 14.7 0 0 1 0-4h4.66a14.7 14.7 0 0 1 0 4Zm.27 5.01A15.7 15.7 0 0 0 15.98 16h2.95a8.04 8.04 0 0 1-4.33 3.01ZM16.41 14a16.8 16.8 0 0 0 0-4h3.33a8.4 8.4 0 0 1 0 4h-3.33Z"/></svg>',
        };
    }
}
