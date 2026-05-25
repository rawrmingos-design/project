<?php

namespace App\Filament\Admin\Resources\ResellerIntegrations;

use App\Filament\Admin\Resources\ResellerIntegrations\Pages\CreateResellerIntegration;
use App\Filament\Admin\Resources\ResellerIntegrations\Pages\EditResellerIntegration;
use App\Filament\Admin\Resources\ResellerIntegrations\Pages\ListResellerIntegrations;
use App\Models\ResellerIntegration;
use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ResellerIntegrationResource extends Resource
{
    protected static ?string $model = ResellerIntegration::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Reseller Integrations';

    protected static ?string $modelLabel = 'Reseller Integration';

    protected static ?string $pluralModelLabel = 'Reseller Integrations';

    protected static ?string $recordTitleAttribute = 'integration_code';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Integration')
                    ->description('Gunakan integration code yang unik dan stabil. Header X-Reseller-Integration-Code pada order live akan diarahkan ke record ini.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Reseller User')
                            ->relationship('user', 'username')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('integration_code')
                            ->label('Integration Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('reseller-live-01')
                            ->helperText('Header X-Reseller-Integration-Code harus sama persis dengan nilai ini.'),
                        Hidden::make('integration_type')
                            ->default('provider'),
                        Hidden::make('credential_source')
                            ->default('global'),
                        Select::make('mode')
                            ->options([
                                'live' => 'live',
                            ])
                            ->default('live')
                            ->required()
                            ->native(false),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        Textarea::make('notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('integration_code')
                    ->label('Integration Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mode')
                    ->badge(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('callbackProfile.is_enabled')
                    ->label('Callback')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Enabled' : 'Disabled')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
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
            'index' => ListResellerIntegrations::route('/'),
            'create' => CreateResellerIntegration::route('/create'),
            'edit' => EditResellerIntegration::route('/{record}/edit'),
        ];
    }
}
