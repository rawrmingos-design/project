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

    protected static ?string $navigationLabel = 'Webhook Keluar';

    protected static ?string $modelLabel = 'Webhook Keluar';

    protected static ?string $pluralModelLabel = 'Webhook Keluar';

    protected static ?string $recordTitleAttribute = 'callback_url';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tujuan Webhook')
                    ->description('Atur endpoint partner/reseller yang akan menerima callback status order dari sistem kita.')
                    ->schema([
                        Select::make('reseller_integration_id')
                            ->label('Connection / Partner')
                            ->relationship('integration', 'integration_code')
                            ->searchable()
                            ->required(),
                        Toggle::make('is_enabled')
                            ->label('Aktif')
                            ->default(true),
                        TextInput::make('callback_url')
                            ->label('URL Tujuan Callback')
                            ->required()
                            ->url()
                            ->maxLength(2048)
                            ->helperText('Live wajib HTTPS dengan host publik. Sandbox boleh memakai localhost, tunnel, atau dev host untuk testing.'),
                        TextInput::make('webhook_secret')
                            ->label('Secret Webhook')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->helperText('Dipakai untuk signature outgoing callback. Kosongkan saat edit jika tidak ingin mengganti secret.'),
                    ])
                    ->columns(2),
                Section::make('Pengaturan Teknis')
                    ->description('Opsional. Jangan ubah bagian ini kecuali partner membutuhkan format signature atau versi payload tertentu.')
                    ->schema([
                        Select::make('signing_algorithm')
                            ->label('Algoritma Signature')
                            ->options([
                                'sha1' => 'sha1',
                                'sha256' => 'sha256',
                                'sha512' => 'sha512',
                            ])
                            ->default('sha256')
                            ->required()
                            ->native(false),
                        TextInput::make('signature_header')
                            ->label('Header Signature')
                            ->default('X-Callback-Signature')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('version')
                            ->label('Versi Payload')
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
                    ->label('Connection / Partner')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('integration.user.username')
                    ->label('User Partner')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('integration.mode')
                    ->label('Mode')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state)),
                IconColumn::make('is_enabled')
                    ->boolean()
                    ->label('Aktif'),
                TextColumn::make('callback_url')
                    ->label('URL Tujuan Callback')
                    ->limit(40)
                    ->copyable(),
                TextColumn::make('updated_at')
                    ->label('Update Terakhir')
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
