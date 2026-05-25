<?php

namespace App\Filament\Admin\Resources\ResellerCallbackProfiles;

use App\Filament\Admin\Resources\ResellerCallbackProfiles\Pages\CreateResellerCallbackProfile;
use App\Filament\Admin\Resources\ResellerCallbackProfiles\Pages\EditResellerCallbackProfile;
use App\Filament\Admin\Resources\ResellerCallbackProfiles\Pages\ListResellerCallbackProfiles;
use App\Models\ResellerCallbackProfile;
use App\Support\ResellerCallbackUrlValidator;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ResellerCallbackProfileResource extends Resource
{
    protected static ?string $model = ResellerCallbackProfile::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Reseller Callbacks';

    protected static ?string $modelLabel = 'Reseller Callback Profile';

    protected static ?string $pluralModelLabel = 'Reseller Callback Profiles';

    protected static ?string $recordTitleAttribute = 'callback_url';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Callback Configuration')
                    ->description('Live callback dikirim sekali lalu dicatat. URL live harus HTTPS dan mengarah ke host publik.')
                    ->schema([
                        Select::make('reseller_integration_id')
                            ->label('Integration')
                            ->relationship('integration', 'integration_code', modifyQueryUsing: fn ($query) => $query->where('mode', 'live'))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(true),
                        TextInput::make('callback_url')
                            ->label('Callback URL')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail): void {
                                    $reason = ResellerCallbackUrlValidator::failureReason((string) $value);

                                    if ($reason !== null) {
                                        $fail($reason);
                                    }
                                };
                            }),
                        TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->helperText('Kosongkan saat edit bila tidak ingin mengganti secret.'),
                        Select::make('signing_algorithm')
                            ->options([
                                'sha1' => 'sha1',
                                'sha256' => 'sha256',
                                'sha512' => 'sha512',
                            ])
                            ->default('sha256')
                            ->required()
                            ->native(false),
                        TextInput::make('signature_header')
                            ->default('X-Callback-Signature')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('version')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('integration.integration_code')
                    ->label('Integration')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('integration.user.username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_enabled')
                    ->boolean()
                    ->label('Enabled'),
                TextColumn::make('callback_url')
                    ->label('Callback URL')
                    ->limit(40)
                    ->copyable(),
                TextColumn::make('signing_algorithm')
                    ->badge(),
                TextColumn::make('version')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListResellerCallbackProfiles::route('/'),
            'create' => CreateResellerCallbackProfile::route('/create'),
            'edit' => EditResellerCallbackProfile::route('/{record}/edit'),
        ];
    }
}
