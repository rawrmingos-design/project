<?php

namespace App\Filament\Admin\Resources\Providers;

use App\Filament\Admin\Resources\Providers\Pages\ManageProviders;
use App\Jobs\CheckProviderBalanceJob;
use App\Jobs\SyncActiveProviderBalancesJob;
use App\Models\Provider;
use App\Support\ProviderBalanceSupport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationLabel = 'Saldo Provider';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Provider';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Provider')
                    ->required()
                    ->maxLength(255),
                Placeholder::make('credential_source')
                    ->label('Sumber Credential')
                    ->content('Credential API provider diatur di Settings > Providers & API. Menu ini hanya untuk status provider dan cek saldo.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Provider')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(Provider $record) => $record->code),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onColor('success')
                    ->offColor('danger'),

                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->weight('bold')
                    ->color(fn($state) => $state < 100000 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('last_check_at')
                    ->label('Terakhir Dicek')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('Edit'),
                Action::make('check_balance')
                    ->label('Cek Saldo')
                    ->icon('heroicon-o-arrow-path')
                    ->disabled(fn(Provider $record): bool => ! in_array($record->code, ProviderBalanceSupport::SUPPORTED_PROVIDER_CODES, true))
                    ->tooltip(fn(Provider $record): ?string => in_array($record->code, ProviderBalanceSupport::SUPPORTED_PROVIDER_CODES, true)
                        ? null
                        : 'Provider ini belum mendukung cek saldo otomatis.')
                    ->action(function (Provider $record) {
                        $lock = Cache::lock('provider-balance-check:' . $record->id, 8);

                        if (!$lock->get()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cek saldo sedang diproses')
                                ->body('Permintaan sebelumnya masih berjalan.')
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            CheckProviderBalanceJob::dispatch($record->id)->afterResponse();

                            \Filament\Notifications\Notification::make()
                                ->title('Cek saldo masuk antrean')
                                ->body('Saldo akan diperbarui otomatis dalam beberapa detik.')
                                ->success()
                                ->send();
                        } finally {
                            $lock->release();
                        }
                    }),
            ])
            ->toolbarActions([
                Action::make('sync_active_balances')
                    ->label('Sync Saldo Provider Aktif')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->action(function (): void {
                        SyncActiveProviderBalancesJob::dispatch()->afterResponse();

                        \Filament\Notifications\Notification::make()
                            ->title('Sync saldo dimulai')
                            ->body('Semua provider aktif sedang di-refresh via queue.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProviders::route('/'),
        ];
    }

}
