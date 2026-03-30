<?php

namespace App\Filament\Admin\Resources\Providers;

use App\Filament\Admin\Resources\Providers\Pages\ManageProviders;
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
                    ->action(function (Provider $record) {
                        $balance = null;
                        // Prepare config override (if populated in this table)
                        $config = [];
                        if (!empty($record->api_username)) $config['username'] = $record->api_username;
                        if (!empty($record->api_username)) $config['api_id'] = $record->api_username; // Handle varied key names
                        if (!empty($record->api_key)) $config['api_key'] = $record->api_key;
                        // Endpoints usually valid for both, or we can add override later if needed.

                        try {
                            switch ($record->code) {
                                case 'digiflazz':
                                    $res = (new \App\Http\Controllers\DigiFlazzController($config))->cekSaldo();
                                    $balance = $res['data']['deposit'] ?? 0;
                                    break;
                                case 'bangjeff':
                                    $res = (new \App\Http\Controllers\provider\BangJeffController($config))->balance();
                                    $balance = $res['data']['balance'] ?? 0;
                                    break;
                                case 'vip':
                                case 'vip_reseller':
                                    $res = (new \App\Http\Controllers\provider\VipResellerController($config))->profile();
                                    $balance = $res['data']['balance']
                                        ?? $res['data']['saldo']
                                        ?? $res['data']['sisa_saldo']
                                        ?? $res['data']['profile']['balance']
                                        ?? $res['data']['profile']['saldo']
                                        ?? $res['balance']
                                        ?? $res['saldo']
                                        ?? null;

                                    if ($balance === null) {
                                        throw new \RuntimeException(
                                            $res['message'] ?? 'VIP Reseller profile/balance response tidak mengandung field balance yang dikenali.'
                                        );
                                    }
                                    break;
                                case 'apigames':
                                    // $res = (new \App\Http\Controllers\provider\ApiGamesController($config))->profile();
                                    $balance = 0;
                                    break;
                                default:
                                    return;
                            }

                            $normalizedBalance = static::normalizeBalanceValue($balance);

                            if ($normalizedBalance === null) {
                                throw new \RuntimeException('Format saldo provider tidak valid: ' . (is_scalar($balance) ? (string) $balance : json_encode($balance)));
                            }

                            $record->update([
                                'balance' => $normalizedBalance,
                                'last_check_at' => now(),
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Balance Updated')
                                ->body('Saldo provider berhasil diperbarui.')
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                             \Filament\Notifications\Notification::make()
                                ->title('Check Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
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

    private static function normalizeBalanceValue(mixed $rawBalance): ?float
    {
        if (is_int($rawBalance) || is_float($rawBalance)) {
            return (float) $rawBalance;
        }

        if (! is_string($rawBalance)) {
            return null;
        }

        $value = trim($rawBalance);

        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? '';

        if ($value === '' || $value === '-' || $value === '.' || $value === ',') {
            return null;
        }

        // Example: 1.234.567 or 1,234,567 (thousands separators only)
        if (preg_match('/^\-?\d{1,3}([.,]\d{3})+$/', $value) === 1) {
            $value = str_replace([',', '.'], '', $value);
        } elseif (str_contains($value, ',') && str_contains($value, '.')) {
            // Mixed separators: infer decimal separator by the last symbol.
            $lastComma = strrpos($value, ',');
            $lastDot = strrpos($value, '.');

            if ($lastComma !== false && $lastDot !== false) {
                if ($lastComma > $lastDot) {
                    $value = str_replace('.', '', $value);
                    $value = str_replace(',', '.', $value);
                } else {
                    $value = str_replace(',', '', $value);
                }
            }
        } elseif (str_contains($value, ',')) {
            // Treat comma as decimal separator.
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
