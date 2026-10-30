<?php

namespace App\Filament\Admin\Resources\RewardRedemptions\Tables;

use App\Models\RewardRedemption;
use App\Services\RewardService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RewardRedemptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('user.username')
                    ->label('Member')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('reward_name')
                    ->label('Hadiah')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('points_spent')
                    ->label('Poin')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->suffix(' poin'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RewardRedemption::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        RewardRedemption::STATUS_PENDING    => 'warning',
                        RewardRedemption::STATUS_PROCESSING => 'info',
                        RewardRedemption::STATUS_SHIPPED    => 'primary',
                        RewardRedemption::STATUS_COMPLETED  => 'success',
                        RewardRedemption::STATUS_REJECTED   => 'danger',
                        default                             => 'gray',
                    }),

                TextColumn::make('city')
                    ->label('Kota')
                    ->toggleable(),

                TextColumn::make('tracking_no')
                    ->label('Resi')
                    ->placeholder('-')
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Ditukar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(RewardRedemption::STATUS_LABELS),
            ])
            ->recordActions([
                EditAction::make()->label('Proses'),

                Action::make('reject_refund')
                    ->label('Tolak & Refund')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (RewardRedemption $record): bool => $record->refunded_at === null
                        && ! in_array($record->status, [RewardRedemption::STATUS_COMPLETED, RewardRedemption::STATUS_REJECTED], true))
                    ->requiresConfirmation()
                    ->modalHeading('Tolak penukaran & kembalikan poin')
                    ->modalDescription('Poin dan stok akan dikembalikan ke member. Tindakan ini tidak bisa dibatalkan.')
                    ->form([
                        Textarea::make('reason')
                            ->label('Alasan penolakan')
                            ->required(),
                    ])
                    ->action(function (RewardRedemption $record, array $data): void {
                        $ok = app(RewardService::class)->refund($record, $data['reason']);

                        $notification = Notification::make()
                            ->title($ok ? 'Penukaran ditolak & poin dikembalikan' : 'Gagal (mungkin sudah di-refund sebelumnya)');

                        ($ok ? $notification->success() : $notification->danger())->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
