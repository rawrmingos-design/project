<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies;

use App\Filament\Admin\Clusters\Integrations;
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

    protected static ?string $cluster = Integrations::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Incoming Rules';

    protected static ?string $modelLabel = 'Incoming Rule';

    protected static ?string $pluralModelLabel = 'Incoming Rules';

    protected static ?string $recordTitleAttribute = 'source_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Incoming Rule')
                    ->description('Atur source callback mana yang boleh masuk ke sistem kita. Gunakan mode log_only sebagai tahap observasi sebelum enforce di production.')
                    ->schema([
                        Select::make('source_domain')
                            ->label('Source Type')
                            ->options([
                                'supplier_callback' => 'Supplier Callback',
                                'payment_gateway' => 'Payment Gateway',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('source_name')
                            ->label('Provider / Gateway')
                            ->placeholder('digiflazz, vip, tripay, duitku, bangjeff')
                            ->helperText('Gunakan nama provider atau gateway yang sama dengan route callback di aplikasi.')
                            ->required()
                            ->maxLength(255),
                        Select::make('mode')
                            ->label('Mode')
                            ->options([
                                'disabled' => 'disabled',
                                'log_only' => 'log_only',
                                'enforce' => 'enforce',
                            ])
                            ->default('log_only')
                            ->required()
                            ->native(false)
                            ->helperText('log_only hanya mencatat. enforce mulai memblokir source IP yang tidak cocok.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        TextInput::make('description')
                            ->label('Short Label')
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
                    ->label('Source Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'supplier_callback' => 'Supplier Callback',
                        'payment_gateway' => 'Payment Gateway',
                        default => $state,
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('source_name')
                    ->label('Provider / Gateway')
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
                    ->label('Allowed IPs')
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
