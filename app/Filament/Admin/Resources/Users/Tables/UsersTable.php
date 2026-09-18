<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Models\PointHistory;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                    
                TextColumn::make('no_wa')
                    ->label('No. WhatsApp')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->toggleable(),
                    
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),

                TextColumn::make('point_balance')
                    ->label('Saldo Poin')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->toggleable(),
                    
                BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'danger' => 'Admin',
                        'success' => 'Platinum',
                        'warning' => 'Gold',
                        'primary' => 'Member',
                    ])
                    ->icons([
                        'heroicon-o-shield-check' => 'Admin',
                        'heroicon-o-star' => 'Platinum',
                        'heroicon-o-trophy' => 'Gold',
                        'heroicon-o-user' => 'Member',
                    ]),
                    

                TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role User')
                    ->options([
                        'Admin' => 'Admin',
                        'Member' => 'Member',
                        'Gold' => 'Gold',
                        'Platinum' => 'Platinum',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Edit'),
                
                Action::make('adjust_balance')
                    ->label('Ubah Saldo')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('warning')
                    ->form([
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->numeric()
                            ->required()
                            ->prefix('Rp')
                            ->helperText('Gunakan nilai negatif untuk mengurangi saldo, positif untuk menambah.'),
                    ])
                    ->action(function ($record, array $data) {
                        $newBalance = $record->balance + $data['amount'];
                        $record->update(['balance' => max(0, $newBalance)]);
                        
                        Notification::make()
                            ->title('Saldo berhasil diubah')
                            ->body("Saldo baru: Rp " . number_format($newBalance, 0, ',', '.'))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Action::make('adjust_points')
                    ->label('Ubah Poin')
                    ->icon('heroicon-o-star')
                    ->color('info')
                    ->form([
                        TextInput::make('amount')
                            ->label('Jumlah Poin')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->rule('not_in:0')
                            ->helperText('Nilai positif menambah poin, negatif mengurangi. Tidak boleh 0.'),
                        
                        TextInput::make('description')
                            ->label('Catatan (opsional)')
                            ->maxLength(255)
                            ->helperText('Tersimpan di riwayat poin user.'),
                    ])
                    ->action(function ($record, array $data) {
                        $amount = (int) $data['amount'];

                        if ($amount === 0) {
                            Notification::make()
                                ->title('Jumlah poin tidak valid')
                                ->body('Masukkan nilai poin selain 0.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $result = DB::transaction(function () use ($record, $amount, $data) {
                            $user = User::where('id', $record->id)->lockForUpdate()->first();

                            if (!$user) {
                                return null;
                            }

                            $currentBalance = (int) ($user->point_balance ?? 0);

                            if ($amount < 0 && abs($amount) > $currentBalance) {
                                return ['status' => 'insufficient', 'balance' => $currentBalance];
                            }

                            $newBalance = $currentBalance + $amount;

                            User::where('id', $user->id)->update(['point_balance' => $newBalance]);

                            $note = trim((string) ($data['description'] ?? ''));

                            PointHistory::create([
                                'user_id'     => $user->id,
                                'order_id'    => null,
                                'type'        => $amount > 0 ? 'earn' : 'redeem',
                                'points'      => abs($amount),
                                'description' => 'Penyesuaian poin admin' . ($note !== '' ? ': ' . $note : ''),
                            ]);

                            return ['status' => 'ok', 'balance' => $newBalance];
                        });

                        if ($result === null) {
                            Notification::make()
                                ->title('Gagal mengubah poin')
                                ->body('User tidak ditemukan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($result['status'] === 'insufficient') {
                            Notification::make()
                                ->title('Saldo poin tidak cukup')
                                ->body('Pengurangan melebihi saldo poin. Saldo saat ini: ' . number_format($result['balance'], 0, ',', '.') . ' poin.')
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Poin berhasil diubah')
                            ->body('Saldo poin baru: ' . number_format($result['balance'], 0, ',', '.') . ' poin')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
