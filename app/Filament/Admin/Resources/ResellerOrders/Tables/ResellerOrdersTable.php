<?php

namespace App\Filament\Admin\Resources\ResellerOrders\Tables;

use App\Jobs\SendPembelianToProviderJob;
use App\Support\PembelianStatus;
use App\Support\ProviderDispatchTracker;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
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
use Illuminate\Support\HtmlString;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ResellerOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // RESELLER-SPECIFIC COLUMNS
                TextColumn::make('resellerIntegration.user.name')
                    ->label('Reseller')
                    ->searchable(['users.name', 'users.username', 'reseller_integrations.api_key_prefix'])
                    ->sortable()
                    ->description(function ($record) {
                        $mode = $record->is_sandbox ? 'SANDBOX' : 'LIVE';
                        $color = $record->is_sandbox ? 'bg-warning-100 text-warning-800' : 'bg-success-100 text-success-800';
                        return new HtmlString(
                            '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' . $color . '">' . $mode . '</span>'
                        );
                    })
                    ->weight('bold'),

                TextColumn::make('resellerIntegration.api_key_prefix')
                    ->label('API Key')
                    ->formatStateUsing(fn($state) => $state ? substr($state, 0, 12) . '...' : '-')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reseller_integration_id')
                    ->label('Integration ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                BadgeColumn::make('is_sandbox')
                    ->label('Mode')
                    ->getStateUsing(fn($record) => $record->is_sandbox ? 'Sandbox' : 'Live')
                    ->colors([
                        'warning' => 'Sandbox',
                        'success' => 'Live',
                    ])
                    ->icons([
                        'heroicon-o-beaker' => 'Sandbox',
                        'heroicon-o-check-circle' => 'Live',
                    ])
                    ->toggleable(),

                // STANDARD ORDER COLUMNS
                TextColumn::make('display_order_id')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->description(fn($record) => $record->display_order_id !== $record->order_id
                        ? 'Canonical: ' . $record->order_id
                        : null)
                    ->weight('bold'),

                TextColumn::make('user_id')
                    ->label('Game Account')
                    ->getStateUsing(function ($record) {
                        $userId = $record->user_id ?? '-';
                        $zone = $record->zone;

                        if ($zone && $zone !== 'N/A' && trim($zone) !== '') {
                            return $userId . ' (' . $zone . ')';
                        }

                        return $userId;
                    })
                    ->description(fn($record) => $record->nickname ?? '-')
                    ->searchable(['user_id', 'nickname', 'zone'])
                    ->sortable(),

                TextColumn::make('layanan')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('status')
                    ->label('Status Provider')
                    ->badge()
                    ->getStateUsing(fn($record) => $record->status_display_label)
                    ->color(fn($record) => $record->status_badge_color)
                    ->icon(fn($record) => $record->status_icon)
                    ->sortable(),

                TextColumn::make('dispatch_state')
                    ->label('Dispatch')
                    ->badge()
                    ->getStateUsing(fn($record): string => ProviderDispatchTracker::label($record->getKey()))
                    ->color(fn($record): string => ProviderDispatchTracker::badgeColor($record->getKey()))
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('pembayaran.status')
                    ->label('Status Pembelian')
                    ->getStateUsing(function ($record) {
                        $paymentStatus = optional($record->pembayaran)->status;
                        return $paymentStatus === 'Lunas' ? 'Success' : 'Pending';
                    })
                    ->colors([
                        'success' => 'Success',
                        'warning' => 'Pending',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Success',
                        'heroicon-o-clock' => 'Pending',
                    ]),

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
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('reseller_integration_id')
                    ->label('Reseller')
                    ->options(function () {
                        return \App\Models\ResellerIntegration::query()
                            ->leftJoin('users', 'users.id', '=', 'reseller_integrations.user_id')
                            ->selectRaw("reseller_integrations.id, COALESCE(NULLIF(users.name, ''), users.username, reseller_integrations.integration_code) as reseller_label")
                            ->orderBy('reseller_label')
                            ->pluck('reseller_label', 'reseller_integrations.id')
                            ->toArray();
                    })
                    ->searchable(),

                SelectFilter::make('is_sandbox')
                    ->label('Order Mode')
                    ->options([
                        '1' => 'Sandbox (Test)',
                        '0' => 'Live (Production)',
                    ])
                    ->default('0'), // Default: show live orders only

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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('View Detail'),

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
                        ->modalDescription('Apakah Anda yakin ingin mengubah status pembelian ini?')
                        ->modalSubmitActionLabel('Ya, Ubah Status'),

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
                        ->modalDescription('Are you sure you want to retry this transaction?'),

                    Action::make('viewIntegration')
                        ->label('View Integration')
                        ->icon('heroicon-o-server')
                        ->color('gray')
                        ->url(fn($record) => $record->resellerIntegration
                            ? route('filament.admin.resources.reseller-applications.view', [
                                'record' => $record->resellerIntegration->reseller_application_id ?? null
                            ])
                            : null)
                        ->visible(fn($record) => $record->resellerIntegration && $record->resellerIntegration->reseller_application_id)
                        ->openUrlInNewTab(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->headerActions([
                ExportAction::make('export_reseller_sales_report')
                    ->label('Export Reseller Sales')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->exports([
                        ExcelExport::make('reseller_sales_report')
                            ->askForFilename()
                            ->askForWriterType()
                            ->withFilename(fn() => 'reseller-orders-' . now()->format('Y-m-d'))
                            ->withColumns(self::salesReportExportColumns()),
                    ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Data (Reseller Orders)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->exports([
                            ExcelExport::make('reseller_sales_report')
                                ->askForFilename()
                                ->askForWriterType()
                                ->withFilename(fn() => 'reseller-orders-' . now()->format('Y-m-d'))
                                ->withColumns(self::salesReportExportColumns()),
                        ]),
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

            Column::make('resellerIntegration.user.name')
                ->heading('RESELLER NAME')
                ->getStateUsing(fn($record) => $record->resellerIntegration?->user?->name),

            Column::make('resellerIntegration.user.username')
                ->heading('RESELLER USERNAME')
                ->getStateUsing(fn($record) => $record->resellerIntegration?->user?->username),

            Column::make('resellerIntegration.api_key_prefix')
                ->heading('API KEY PREFIX')
                ->getStateUsing(fn($record) => $record->resellerIntegration?->api_key_prefix),

            Column::make('is_sandbox')
                ->heading('MODE')
                ->getStateUsing(fn($record) => $record->isSandboxOrder() ? 'Sandbox' : 'Live'),

            Column::make('status')
                ->heading('STATUS PROVIDER')
                ->getStateUsing(fn($record) => $record->status_display_label ?? $record->status),

            Column::make('layanan')
                ->heading('PRODUK')
                ->getStateUsing(fn($record) => $record->layanan),

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
                ->heading('HARGA')
                ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                ->getStateUsing(fn($record) => $record->harga),

            Column::make('profit')
                ->heading('PROFIT')
                ->formatStateUsing(fn($state) => 'Rp ' . number_format((float) $state, 0, ',', '.'))
                ->getStateUsing(fn($record) => $record->profit),

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
}
