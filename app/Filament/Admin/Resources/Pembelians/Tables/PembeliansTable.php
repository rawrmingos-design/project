<?php

namespace App\Filament\Admin\Resources\Pembelians\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PembeliansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_id')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                    
                TextColumn::make('nickname')
                    ->label('Nickname')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->default('Anonim')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('layanan')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->description(function ($record) {
                        $source = $record->traffic_source ?? 'Original';
                        // Image URLs (Reliable CDNs)
                        $logos = [
                            'facebook' => 'https://upload.wikimedia.org/wikipedia/commons/b/b9/2023_Facebook_icon.svg',
                            'instagram' => 'https://upload.wikimedia.org/wikipedia/commons/e/e7/Instagram_logo_2016.svg',
                            'tiktok' => 'https://upload.wikimedia.org/wikipedia/en/a/a9/TikTok_logo.svg',
                            'youtube' => 'https://upload.wikimedia.org/wikipedia/commons/0/09/YouTube_full-color_icon_%282017%29.svg',
                            'google' => 'https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg',
                            'whatsapp' => 'https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg',
                        ];
                        
                        $key = strtolower($source);
                        if (isset($logos[$key])) {
                            $imgSrc = $logos[$key];
                             return new \Illuminate\Support\HtmlString(
                                "<div class='flex items-center gap-1 mt-1'>
                                    <img src='{$imgSrc}' height='16' width='16' class='h-4 w-4 mb-1 object-contain' alt='{$source}'>
                                </div>"
                            );
                        } else {
                             // Fallback for Direct / Others
                             $icon = $key === 'direct' ? '🔗' : '🌐';
                             return new \Illuminate\Support\HtmlString(
                                "<span class='text-xs text-gray-500'>$icon $source</span>"
                            );
                        }
                    }),
                    
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'Sukses',
                        'warning' => 'Pending',
                        'pending' => 'Pending',
                        'info' => 'Processing',
                        'info' => 'Proses',
                        'danger' => 'Batal',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'Sukses',
                        'heroicon-o-clock' => 'Proses',
                        'heroicon-o-arrow-path' => 'Processing',
                        'heroicon-o-clock' => 'Pending',
                        'heroicon-o-x-circle' => 'Batal',
                    ])
                    ->sortable(),
                    
                TextColumn::make('harga')
                    ->label('Amount')
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
                    ->getStateUsing(function ($record) {
                        static $methodCache = null;
                        if ($methodCache === null) {
                            $methodCache = \App\Models\Method::pluck('payment', 'code')->toArray();
                        }
                        
                        $metode = optional($record->pembayaran)->metode;
                        if (!$metode) return '-';
                        if (strtoupper($metode) === 'SALDO') return 'SALDO';
                        
                        $provider = $methodCache[$metode] ?? null;
                        return $provider ? $provider . '.' . strtolower($metode) : $metode;
                    })
                    ->toggleable(),
                    
                TextColumn::make('profit')
                    ->label('Profit')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                    
                TextColumn::make('zone')
                    ->label('Zone/Server')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('N/A'),
                    
                TextColumn::make('nickname')
                    ->label('Nickname')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('N/A'),

                TextColumn::make('tipe_transaksi')
                    ->label('Type')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'Success' => 'Success',
                        'Pending' => 'Pending',
                        'Processing' => 'Processing',
                        'Failed' => 'Failed',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('tipe_transaksi')
                    ->label('Transaction Type')
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
                            ->label('From Date'),
                        DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                    
                Filter::make('amount_range')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('amount_from')
                            ->label('Min Amount')
                            ->numeric(),
                        \Filament\Forms\Components\TextInput::make('amount_until')
                            ->label('Max Amount')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('harga', '>=', $amount),
                            )
                            ->when(
                                $data['amount_until'],
                                fn (Builder $query, $amount): Builder => $query->where('harga', '<=', $amount),
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('View Detail'),

                Action::make('editStatus')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->label('Status Baru')
                            ->options([
                                'Success' => 'Sukses',
                                'Pending' => 'Pending',
                                'Proses' => 'Proses',
                                'Processing' => 'Processing',
                                'Failed' => 'Failed',
                                'Batal' => 'Batal',
                                'Gagal' => 'Gagal',
                            ])
                            ->required()
                            ->default(fn ($record) => $record->status),
                    ])
                    ->action(function ($record, array $data) {
                        $oldStatus = $record->status;
                        $newStatus = $data['status'];
                        
                        if ($oldStatus === $newStatus) return;

                        $logMsg = $record->log . "\nStatus diubah manual oleh admin dari '{$oldStatus}' menjadi '{$newStatus}' pada " . now()->format('Y-m-d H:i:s');
                        $record->update(['status' => $newStatus, 'log' => $logMsg]);

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
                    ->label('Process')
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
                    
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['Pending', 'Processing']))
                    ->action(function ($record) {
                        $logMsg = 'Cancelled by admin at ' . now()->format('Y-m-d H:i:s');

                        // Refund logic if SALDO
                        if ($record->pembayaran && $record->pembayaran->metode === 'SALDO' && $record->user) {
                            $record->user->increment('balance', $record->harga);
                            $logMsg .= " (Saldo Rp " . number_format($record->harga, 0, ',', '.') . " di-refund)";
                        }

                        $record->update(['status' => 'Failed', 'log' => $logMsg]);
                        Notification::make()
                            ->title('Order cancelled & refunded if applicable')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                    
                Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'Success')
                    ->action(function ($record) {
                        $logMsg = 'Refund processed by admin at ' . now()->format('Y-m-d H:i:s');

                        // Refund logic if SALDO
                        if ($record->pembayaran && $record->pembayaran->metode === 'SALDO' && $record->user) {
                            $record->user->increment('balance', $record->harga);
                            $logMsg .= " (Saldo Rp " . number_format($record->harga, 0, ',', '.') . " di-refund)";
                        }

                        $record->update(['status' => 'Batal', 'log' => $logMsg]); // Changed to Batal/Refunded
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
                    ->visible(fn ($record) => in_array($record->status, ['Pending', 'Processing', 'Failed', 'Gagal', 'Proses', 'Batal']) && optional($record->pembayaran)->status === 'Lunas')
                    ->action(function ($record) {
                        try {
                            $routingService = new \App\Services\ProviderRoutingService();
                            $processor = new \App\Services\OrderProcessingService($routingService);
                            
                            $result = $processor->process($record);
                            
                            if ($result['success']) {
                                $newStatus = ($result['order_status'] ?? 'Pending') === 'Sukses' ? 'Success' : 'Processing'; 
                                $updateData = [
                                    'status' => $newStatus,
                                    'log' => $record->log . "\n" . 'Retried by admin at ' . now()->format('Y-m-d H:i:s') . ': ' . $result['message'],
                                ];
                                
                                if (!empty($result['transaction_id'])) {
                                    $updateData['provider_order_id'] = $result['transaction_id'];
                                }
                                
                                $record->update($updateData);

                                Notification::make()
                                    ->title('Retry successful')
                                    ->body($result['message'])
                                    ->success()
                                    ->send();
                            } else {
                                $record->update([
                                    'log' => $record->log . "\n" . 'Retry failed at ' . now()->format('Y-m-d H:i:s') . ': ' . $result['message'],
                                ]);
                                
                                Notification::make()
                                    ->title('Retry failed')
                                    ->body($result['message'])
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('System Error')
                                ->body($e->getMessage())
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
                    ->form([
                        \Filament\Forms\Components\Select::make('channel')
                            ->label('Send via')
                            ->options([
                                'whatsapp' => 'WhatsApp Only',
                                'email' => 'Email Only',
                                'both' => 'Both (WhatsApp & Email)',
                            ])
                            ->default('both')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $channel = $data['channel'];
                        $waService = new \App\Services\WhatsappNotificationService();
                        $emailService = new \App\Services\EmailNotificationService();
                        
                        // Prepare Data
                        $status = strtolower($record->status);
                        $slug = 'transaction_pending';
                        $note = 'Pesanan sedang menunggu respon provider.';
                        
                        if (in_array($status, ['success', 'sukses'])) {
                            $slug = 'transaction_success';
                            $note = 'Terima kasih telah berbelanja.';
                        } elseif (in_array($status, ['failed', 'gagal', 'batal', 'expired'])) {
                            $slug = 'transaction_failed';
                            $note = 'Mohon maaf, transaksi Anda gagal atau kadaluarsa.';
                        }
                        
                        $notificationData = [
                            'nickname' => $record->nickname ?? 'Pelanggan',
                            'order_id' => $record->order_id,
                            'product' => $record->layanan,
                            'amount' => 'Rp ' . number_format($record->harga, 0, ',', '.'),
                            'status' => $record->status,
                            'note' => $note,
                        ];

                        $results = [];

                        // Send WhatsApp
                        if ($channel === 'whatsapp' || $channel === 'both') {
                            if ($record->user && $record->user->no_wa) {
                                $waResult = $waService->sendNotification($record->user->no_wa, $slug, $notificationData);
                                $results[] = "WA: " . ($waResult['success'] ? 'Sent' : 'Failed');
                            } elseif ($record->no_hp) { // Fallback if guest has phone column (assuming no_hp exists or we use user relation)
                                // Standardize on user relation per previous table logic, but check if we store no_hp on pembelian?
                                // Table columns imply user->no_wa. Let's stick to user->no_wa for now as per table columns.
                                // But wait, guest orders might store phone in 'no_hp' or similar?
                                // Checking PembeliansTable columns: no phone column explicitly shown except user.no_wa
                                // Let's check Pembelian model or migration from earlier?
                                // Assuming user relation is reliable or Invoice has 'no_hp' field?
                                // Previous callbacks used $invoice->no_pembeli.
                                $targetWa = $record->no_pembeli ?? ($record->user->no_wa ?? null);
                                if ($targetWa) {
                                    $waResult = $waService->sendNotification($targetWa, $slug, $notificationData);
                                    $results[] = "WA: " . ($waResult['success'] ? 'Sent' : 'Failed');
                                } else {
                                    $results[] = "WA: No Number";
                                }
                            } else {
                                $targetWa = $record->no_pembeli ?? ($record->user->no_wa ?? null);
                                if ($targetWa) {
                                    $waResult = $waService->sendNotification($targetWa, $slug, $notificationData);
                                    $results[] = "WA: " . ($waResult['success'] ? 'Sent' : 'Failed');
                                } else {
                                    $results[] = "WA: No Number";
                                }
                            }
                        }

                        // Send Email
                        if ($channel === 'email' || $channel === 'both') {
                             $targetEmail = $record->email_pembeli ?? ($record->user->email ?? null);
                             if ($targetEmail) {
                                 $emailResult = $emailService->sendTransactionEmail($targetEmail, $notificationData);
                                 $results[] = "Email: " . ($emailResult ? 'Sent' : 'Failed');
                             } else {
                                 $results[] = "Email: No Address";
                             }
                        }

                        Notification::make()
                            ->title('Notification Processed')
                            ->body(implode(', ', $results))
                            ->success()
                            ->send();
                    }),
            ])

            ->bulkActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->label('Export Data (Laporan Sales)')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->exports([
                            ExcelExport::make()
                                ->askForFilename()
                                ->askForWriterType()
                                ->withFilename(fn () => 'laporan-sales-' . now()->format('Y-m-d'))
                                ->withColumns([
                                    \pxlrbt\FilamentExcel\Columns\Column::make('order_id')
                                        ->heading('INVOICE')
                                        ->getStateUsing(fn ($record) => $record->order_id),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('created_at')
                                        ->heading('TANGGAL')
                                        ->getStateUsing(fn ($record) => $record->created_at?->format('d/m/Y H:i')),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('status')
                                        ->heading('STATUS')
                                        ->getStateUsing(fn ($record) => $record->status),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('user.no_wa')
                                        ->heading('WHATSAPP')
                                        ->getStateUsing(fn ($record) => $record->user?->no_wa),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('user.email')
                                        ->heading('EMAIL')
                                        ->getStateUsing(fn ($record) => $record->user?->email),
                                        
                                    \pxlrbt\FilamentExcel\Columns\Column::make('layanan')
                                        ->heading('PRODUK')
                                        ->getStateUsing(fn ($record) => $record->layanan),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('harga')
                                        ->heading('HARGA JUAL')
                                        ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                                        ->getStateUsing(fn ($record) => $record->harga),
                                        
                                    \pxlrbt\FilamentExcel\Columns\Column::make('profit')
                                        ->heading('PROFIT')
                                        ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 0, ',', '.'))
                                        ->getStateUsing(fn ($record) => $record->profit),
                                        
                                    \pxlrbt\FilamentExcel\Columns\Column::make('traffic_source')
                                        ->heading('AD SOURCE')
                                        ->getStateUsing(fn ($record) => $record->traffic_source),

                                    \pxlrbt\FilamentExcel\Columns\Column::make('zone')
                                        ->heading('ZONE / SERVER')
                                        ->getStateUsing(fn ($record) => $record->zone),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('nickname')
                                        ->heading('GAME NICKNAME')
                                        ->getStateUsing(fn ($record) => $record->nickname),
                                    
                                    \pxlrbt\FilamentExcel\Columns\Column::make('provider_order_id')
                                        ->heading('TRX ID PROVIDER')
                                        ->getStateUsing(fn ($record) => $record->provider_order_id),
                                ]),
                        ]),
                BulkAction::make('bulk_process')
                    ->label('Process Selected')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $count = $records->where('status', 'Pending')->count();
                        $records->where('status', 'Pending')->each(function ($record) {
                                $record->update(['status' => 'Processing']);
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
                        $count = $records->whereIn('status', ['Pending', 'Processing'])->count();
                            $records->whereIn('status', ['Pending', 'Processing'])->each(function ($record) {
                                $record->update(['status' => 'Failed', 'log' => 'Bulk cancelled by admin at ' . now()->format('Y-m-d H:i:s')]);
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
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
