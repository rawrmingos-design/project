<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AffiliateRequestResource\Pages;
use App\Models\User;
use App\Services\AffiliateApplicationReviewService;
use App\Services\AffiliateReviewNotificationService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use BackedEnum;
use UnitEnum;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AffiliateRequestResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Permintaan Affiliate';
    
    protected static ?string $pluralLabel = 'Permintaan Affiliate';

    protected static UnitEnum|string|null $navigationGroup = 'Affiliate System';
    
    protected static ?string $slug = 'affiliate-requests';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('affiliate_status', ['pending', 'rejected', 'active']);
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->disabled(),
                TextInput::make('username')
                    ->disabled(),
                TextInput::make('email')
                    ->disabled(),
                TextInput::make('no_wa')
                    ->label('No. WhatsApp')
                    ->disabled(),
                TextInput::make('promotion_channel_url')
                    ->label('URL Channel Promosi')
                    ->formatStateUsing(fn (User $record): string => (string) (data_get($record->affiliate_application_meta, 'promotion_channel_url') ?: '-'))
                    ->disabled(),
                Textarea::make('affiliate_application_note')
                    ->label('Catatan dari User')
                    ->rows(3)
                    ->disabled(),
                Textarea::make('review_note')
                    ->label('Catatan Review Terakhir')
                    ->formatStateUsing(fn (User $record): string => (string) (data_get($record->affiliate_application_meta, 'review_last.note') ?: '-'))
                    ->rows(3)
                    ->disabled(),
                Select::make('affiliate_status')
                    ->options([
                        'inactive' => 'Inactive',
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'rejected' => 'Rejected',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Tgl Daftar'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('no_wa')
                    ->label('No. WhatsApp')
                    ->toggleable(),
                TextColumn::make('affiliate_requested_at')
                    ->label('Tgl Pengajuan')
                    ->since()
                    ->sortable(),
                TextColumn::make('promotion_channel_url')
                    ->label('Channel Promosi')
                    ->state(fn (User $record): string => (string) (data_get($record->affiliate_application_meta, 'promotion_channel_url') ?: '-'))
                    ->url(fn (User $record) => data_get($record->affiliate_application_meta, 'promotion_channel_url'))
                    ->openUrlInNewTab()
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('affiliate_application_note')
                    ->label('Catatan User')
                    ->state(fn (User $record): string => (string) ($record->affiliate_application_note ?: '-'))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('review_note')
                    ->label('Catatan Review')
                    ->state(fn (User $record): string => (string) (data_get($record->affiliate_application_meta, 'review_last.note') ?: '-'))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('reviewed_by')
                    ->label('Reviewer')
                    ->state(fn (User $record): string => (string) (data_get($record->affiliate_application_meta, 'review_last.reviewed_by_username') ?: '-'))
                    ->toggleable(),
                TextColumn::make('reviewed_at')
                    ->label('Waktu Review')
                    ->state(function (User $record): string {
                        $value = data_get($record->affiliate_application_meta, 'review_last.reviewed_at');
                        if (blank($value)) {
                            return '-';
                        }

                        try {
                            return Carbon::parse((string) $value)->format('d M Y H:i');
                        } catch (\Throwable) {
                            return (string) $value;
                        }
                    })
                    ->toggleable(),
                TextColumn::make('notification_delivery')
                    ->label('Notif Terakhir')
                    ->state(function (User $record): string {
                        $notification = data_get($record->affiliate_application_meta, 'review_last.notification');
                        if (! is_array($notification)) {
                            return '-';
                        }

                        $wa = data_get($notification, 'wa');
                        $email = data_get($notification, 'email');

                        $waPart = is_array($wa) && data_get($wa, 'enabled')
                            ? ('WA:' . (data_get($wa, 'success') ? 'ok' : (data_get($wa, 'attempted') ? 'fail' : 'skip')))
                            : 'WA:off';
                        $emailPart = is_array($email) && data_get($email, 'enabled')
                            ? ('Email:' . (data_get($email, 'success') ? 'ok' : (data_get($email, 'attempted') ? 'fail' : 'skip')))
                            : 'Email:off';

                        return $waPart . ' | ' . $emailPart;
                    })
                    ->toggleable(),
                BadgeColumn::make('affiliate_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'active',
                        'danger' => 'rejected',
                    ]),
            ])
            ->filters([
                SelectFilter::make('affiliate_status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'rejected' => 'Rejected',
                        'active' => 'Active',
                    ]),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Terima')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn (User $record): bool => strtolower((string) $record->affiliate_status) === 'pending')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Catatan Review (Opsional)')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (array $data, User $record): void {
                        try {
                            app(AffiliateApplicationReviewService::class)->approve(
                                $record,
                                auth()->user(),
                                (string) ($data['review_note'] ?? '')
                            );

                            Notification::make()
                                ->success()
                                ->title('Pengajuan affiliate disetujui.')
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal memproses pengajuan')
                                ->body(collect($exception->errors())->flatten()->first() ?: 'Status pengajuan tidak valid.')
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);
                            Notification::make()
                                ->danger()
                                ->title('Terjadi kesalahan sistem')
                                ->body('Silakan coba lagi beberapa saat.')
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
                    
                Action::make('reject')
                    ->label('Tolak')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (User $record): bool => strtolower((string) $record->affiliate_status) === 'pending')
                    ->form([
                        Textarea::make('review_note')
                            ->label('Alasan Penolakan')
                            ->rows(3)
                            ->minLength(5)
                            ->maxLength(500)
                            ->required(),
                    ])
                    ->action(function (array $data, User $record): void {
                        try {
                            app(AffiliateApplicationReviewService::class)->reject(
                                $record,
                                auth()->user(),
                                (string) ($data['review_note'] ?? '')
                            );

                            Notification::make()
                                ->success()
                                ->title('Pengajuan affiliate ditolak.')
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->danger()
                                ->title('Gagal memproses pengajuan')
                                ->body(collect($exception->errors())->flatten()->first() ?: 'Status pengajuan tidak valid.')
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);
                            Notification::make()
                                ->danger()
                                ->title('Terjadi kesalahan sistem')
                                ->body('Silakan coba lagi beberapa saat.')
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
                Action::make('resend_notification')
                    ->label('Kirim Ulang Notif')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('gray')
                    ->visible(fn (User $record): bool => in_array(strtolower((string) $record->affiliate_status), ['active', 'rejected'], true))
                    ->action(function (User $record): void {
                        $decision = strtolower((string) $record->affiliate_status);
                        $reviewNote = (string) (data_get($record->affiliate_application_meta, 'review_last.note') ?? '');

                        try {
                            $delivery = app(AffiliateReviewNotificationService::class)
                                ->notifyReviewDecision($record, $decision, $reviewNote);

                            $meta = is_array($record->affiliate_application_meta) ? $record->affiliate_application_meta : [];
                            $reviewLast = is_array(data_get($meta, 'review_last')) ? data_get($meta, 'review_last') : [];
                            $reviewLast['notification'] = array_merge($delivery, ['sent_at' => now()->toIso8601String()]);
                            $meta['review_last'] = $reviewLast;
                            $record->affiliate_application_meta = $meta;
                            $record->save();

                            Notification::make()
                                ->success()
                                ->title('Notifikasi affiliate berhasil dikirim ulang.')
                                ->send();
                        } catch (\Throwable $exception) {
                            report($exception);
                            Notification::make()
                                ->danger()
                                ->title('Gagal kirim ulang notifikasi')
                                ->body('Silakan cek konfigurasi WA/Email dan coba lagi.')
                                ->send();
                        }
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliateRequests::route('/'),
        ];
    }
}
