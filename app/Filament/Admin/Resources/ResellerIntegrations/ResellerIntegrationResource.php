<?php

namespace App\Filament\Admin\Resources\ResellerIntegrations;

use App\Filament\Admin\Clusters\Integrations;
use App\Filament\Admin\Resources\ResellerIntegrations\Pages\CreateResellerIntegration;
use App\Filament\Admin\Resources\ResellerIntegrations\Pages\EditResellerIntegration;
use App\Filament\Admin\Resources\ResellerIntegrations\Pages\ListResellerIntegrations;
use App\Models\InboundSourcePolicy;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class ResellerIntegrationResource extends Resource
{
    protected static ?string $model = ResellerIntegration::class;

    protected static ?string $cluster = Integrations::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-link';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Connections';

    protected static ?string $modelLabel = 'Connection';

    protected static ?string $pluralModelLabel = 'Connections';

    protected static ?string $recordTitleAttribute = 'integration_code';

    public static function sharedIncomingSnapshot(): array
    {
        static $snapshot = null;

        if ($snapshot !== null) {
            return $snapshot;
        }

        $policies = InboundSourcePolicy::query()
            ->where('is_active', true)
            ->withCount([
                'entries' => fn ($query) => $query->where('is_active', true),
            ])
            ->get();

        $activeRules = $policies->count();
        $protectedRules = $policies->filter(fn (InboundSourcePolicy $policy): bool => $policy->entries_count > 0)->count();
        $allowedIps = (int) $policies->sum('entries_count');

        $snapshot = [
            'active_rules' => $activeRules,
            'protected_rules' => $protectedRules,
            'allowed_ips' => $allowedIps,
            'configured' => $protectedRules > 0,
            'label' => $protectedRules > 0
                ? 'Configured'
                : ($activeRules > 0 ? 'Needs IPs' : 'Not set'),
            'color' => $protectedRules > 0
                ? 'success'
                : ($activeRules > 0 ? 'warning' : 'gray'),
        ];

        return $snapshot;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Connection')
                    ->description('Buat satu connection per partner untuk live atau sandbox. Header X-Reseller-Integration-Code pada order akan diarahkan ke record sesuai mode request.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Partner User')
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
                                'sandbox' => 'sandbox',
                            ])
                            ->default('live')
                            ->required()
                            ->native(false)
                            ->helperText('Live dipakai untuk order produksi. Sandbox dipakai untuk testing integrator dengan order simulasi lokal.'),
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
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'callbackProfile', 'latestCallbackDelivery']))
            ->columns([
                TextColumn::make('integration_code')
                    ->label('Integration Code')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('user.username')
                    ->label('Partner User')
                    ->searchable()
                    ->sortable(),
                BadgeColumn::make('mode')
                    ->label('Mode')
                    ->colors([
                        'success' => 'live',
                        'info' => 'sandbox',
                    ])
                    ->formatStateUsing(fn (?string $state): string => Str::headline((string) $state)),
                TextColumn::make('api_key_hint')
                    ->label('API Key')
                    ->placeholder('Not generated')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('api_key_rotated_at')
                    ->label('Key Rotated')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('incoming_shared_readiness')
                    ->label('Incoming (Shared)')
                    ->state(fn (): string => static::sharedIncomingSnapshot()['label'])
                    ->color(fn (): string => static::sharedIncomingSnapshot()['color'])
                    ->tooltip('Incoming rules berlaku shared per source/provider, bukan dikaitkan 1:1 ke connection ini.'),
                BadgeColumn::make('outbound_readiness')
                    ->label('Outgoing')
                    ->state(fn (ResellerIntegration $record): string => $record->outboundReadinessSummary()['label'])
                    ->color(fn (ResellerIntegration $record): string => $record->outboundReadinessSummary()['color']),
                BadgeColumn::make('overall_readiness')
                    ->label('Readiness')
                    ->state(fn (ResellerIntegration $record): string => $record->overallReadinessSummary(static::sharedIncomingSnapshot()['configured'])['label'])
                    ->color(fn (ResellerIntegration $record): string => $record->overallReadinessSummary(static::sharedIncomingSnapshot()['configured'])['color']),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('latest_callback_delivery_status')
                    ->label('Last Delivery')
                    ->state(function (ResellerIntegration $record): string {
                        $status = $record->latestCallbackDelivery?->status;

                        return $status ? Str::headline($status) : 'No logs';
                    })
                    ->badge()
                    ->color(function (ResellerIntegration $record): string {
                        return match ($record->latestCallbackDelivery?->status) {
                            'delivered' => 'success',
                            'failed' => 'danger',
                            'pending' => 'warning',
                            default => 'gray',
                        };
                    }),
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
