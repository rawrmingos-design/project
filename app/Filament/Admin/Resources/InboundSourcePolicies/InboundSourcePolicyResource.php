<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies;

use App\Filament\Admin\Resources\InboundSourcePolicies\Pages\CreateInboundSourcePolicy;
use App\Filament\Admin\Resources\InboundSourcePolicies\Pages\EditInboundSourcePolicy;
use App\Filament\Admin\Resources\InboundSourcePolicies\Pages\ListInboundSourcePolicies;
use App\Filament\Admin\Resources\InboundSourcePolicies\RelationManagers\EntriesRelationManager;
use App\Models\InboundSourcePolicy;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class InboundSourcePolicyResource extends Resource
{
    protected static ?string $model = InboundSourcePolicy::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Inbound Whitelist';

    protected static ?string $modelLabel = 'Inbound Source Policy';

    protected static ?string $pluralModelLabel = 'Inbound Source Policies';

    protected static ?string $recordTitleAttribute = 'source_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Policy')
                    ->description('Kelola whitelist inbound untuk callback supplier dan payment gateway. Gunakan mode log_only sebagai baseline sampai audit proxy selesai.')
                    ->schema([
                        Select::make('source_domain')
                            ->label('Source Domain')
                            ->options([
                                'supplier_callback' => 'Supplier Callback',
                                'payment_gateway' => 'Payment Gateway',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('source_name')
                            ->label('Source Name')
                            ->placeholder('digiflazz, vip, tripay, duitku, bangjeff')
                            ->helperText('Gunakan slug provider yang sama dengan middleware route callback.')
                            ->required()
                            ->maxLength(255),
                        Select::make('mode')
                            ->options([
                                'disabled' => 'disabled',
                                'log_only' => 'log_only',
                                'enforce' => 'enforce',
                            ])
                            ->default('log_only')
                            ->required()
                            ->native(false)
                            ->helperText('enforce hanya aman bila TrustProxies dan chain proxy sudah diaudit.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        TextInput::make('description')
                            ->label('Short Description')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source_domain')
                    ->label('Domain')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'supplier_callback' => 'Supplier Callback',
                        'payment_gateway' => 'Payment Gateway',
                        default => $state,
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('source_name')
                    ->label('Source Name')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('mode')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'enforce' => 'danger',
                        'log_only' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('entries_count')
                    ->label('Entries')
                    ->counts('entries'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source_domain')
                    ->options([
                        'supplier_callback' => 'Supplier Callback',
                        'payment_gateway' => 'Payment Gateway',
                    ]),
                SelectFilter::make('mode')
                    ->options([
                        'disabled' => 'disabled',
                        'log_only' => 'log_only',
                        'enforce' => 'enforce',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('source_domain')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            EntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInboundSourcePolicies::route('/'),
            'create' => CreateInboundSourcePolicy::route('/create'),
            'edit' => EditInboundSourcePolicy::route('/{record}/edit'),
        ];
    }
}
