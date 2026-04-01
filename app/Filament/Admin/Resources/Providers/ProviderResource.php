<?php

namespace App\Filament\Admin\Resources\Providers;

use App\Filament\Admin\Resources\Providers\Pages\ManageProviders;
use App\Jobs\CheckProviderBalanceJob;
use App\Jobs\SyncActiveProviderBalancesJob;
use App\Models\Provider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\Cache;
use UnitEnum;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static ?string $navigationLabel = 'Provider';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $title = 'Provider';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-server-stack';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('api_username')
                    ->label('API Username / ID')
                    ->helperText('Opsional: Override API Username / ID.')
                    ->maxLength(255),
                TextInput::make('api_key')
                    ->label('API Key / Secret')
                    ->password()
                    ->revealable()
                    ->helperText('Warning: Gunakan API Key Production.')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Service Provider')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Provider $record) => $record->code),
                
                ToggleColumn::make('is_active')
                    ->label('Online / Offline')
                    ->onColor('success')
                    ->offColor('danger'),
                    
                TextColumn::make('balance')
                    ->label('Balance')
                    ->money('IDR')
                    ->weight('bold')
                    ->color(fn ($state) => $state < 100000 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('last_check_at')
                    ->label('Last Update')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                Action::make('check_balance')
                    ->label('Check Balance')
                    ->icon('heroicon-o-arrow-path')
                    ->disabled(fn (Provider $record): bool => ! in_array($record->code, ['digiflazz', 'bangjeff', 'vip', 'vip_reseller'], true))
                    ->tooltip(fn (Provider $record): ?string => in_array($record->code, ['digiflazz', 'bangjeff', 'vip', 'vip_reseller'], true)
                        ? null
                        : 'Provider ini belum mendukung check balance otomatis.')
                    ->action(function (Provider $record) {
                        $lock = Cache::lock('provider-balance-check:' . $record->id, 8);

                        if (! $lock->get()) {
                            \Filament\Notifications\Notification::make()
                                ->title('Check sedang diproses')
                                ->body('Permintaan check sebelumnya masih berjalan.')
                                ->warning()
                                ->send();

                            return;
                        }

                        try {
                            CheckProviderBalanceJob::dispatch($record->id)->afterResponse();

                            \Filament\Notifications\Notification::make()
                                ->title('Check balance masuk antrean')
                                ->body('Saldo akan ter-update otomatis dalam beberapa detik.')
                                ->success()
                                ->send();
                        } finally {
                            $lock->release();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                Action::make('sync_active_balances')
                    ->label('Sync Active Balances')
                    ->icon('heroicon-o-bolt')
                    ->color('primary')
                    ->action(function (): void {
                        SyncActiveProviderBalancesJob::dispatch()->afterResponse();

                        \Filament\Notifications\Notification::make()
                            ->title('Sync aktif dimulai')
                            ->body('Semua provider aktif sedang di-refresh via queue.')
                            ->success()
                            ->send();
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProviders::route('/'),
        ];
    }

}
