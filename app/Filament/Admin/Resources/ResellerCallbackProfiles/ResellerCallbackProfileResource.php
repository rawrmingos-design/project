<?php

namespace App\Filament\Admin\Resources\ResellerCallbackProfiles;

use App\Filament\Admin\Clusters\Integrations;
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

    protected static ?string $cluster = Integrations::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Outgoing Webhooks';

    protected static ?string $modelLabel = 'Outgoing Webhook';

    protected static ?string $pluralModelLabel = 'Outgoing Webhooks';

    protected static ?string $recordTitleAttribute = 'callback_url';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Webhook Destination')
                    ->description('Atur ke mana server kita mengirim callback status order live atau sandbox. Live harus pakai target publik, sedangkan sandbox boleh pakai URL lokal untuk testing.')
                    ->schema([
                        Select::make('reseller_integration_id')
                            ->label('Connection')
                            ->relationship('integration', 'integration_code')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Toggle::make('is_enabled')
                            ->label('Enabled')
                            ->default(true),
                        TextInput::make('callback_url')
                            ->label('Destination URL')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->helperText('Sandbox connection boleh memakai localhost/tunnel/dev host. Live connection tetap wajib HTTPS + host publik.'),
                        TextInput::make('webhook_secret')
                            ->label('Webhook Secret')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->helperText('Kosongkan saat edit bila tidak ingin mengganti secret.'),
                    ])
                    ->columns(2),
                Section::make('Advanced')
                    ->description('Sebagian besar client tidak perlu mengubah bagian ini. Default yang ada biasanya sudah cukup.')
                    ->schema([
                        Select::make('signing_algorithm')
                            ->label('Signing Algorithm')
                            ->options([
                                'sha1' => 'sha1',
                                'sha256' => 'sha256',
                                'sha512' => 'sha512',
                            ])
                            ->default('sha256')
                            ->required()
                            ->native(false),
                        TextInput::make('signature_header')
                            ->label('Signature Header')
                            ->default('X-Callback-Signature')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('version')
                            ->label('Version')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('integration.integration_code')
                    ->label('Connection')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('integration.user.username')
                    ->label('Partner User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('integration.mode')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
                IconColumn::make('is_enabled')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('callback_url')
                    ->label('Destination URL')
                    ->limit(40)
                    ->copyable(),
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
