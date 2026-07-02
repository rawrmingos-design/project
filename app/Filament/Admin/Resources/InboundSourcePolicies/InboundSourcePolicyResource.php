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

    protected static ?string $navigationLabel = 'Whitelist IP Callback';

    protected static ?string $modelLabel = 'Rule Whitelist';

    protected static ?string $pluralModelLabel = 'Whitelist IP Callback';

    protected static ?string $recordTitleAttribute = 'source_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rule Whitelist Callback')
                    ->description('Atur IP mana yang boleh mengakses callback supplier atau payment gateway. Gunakan mode Pantau Saja sebelum mengaktifkan blokir di production.')
                    ->schema([
                        Select::make('source_domain')
                            ->label('Jenis Callback')
                            ->options([
                                'supplier_callback' => 'Callback Supplier',
                                'payment_gateway' => 'Payment Gateway',
                            ])
                            ->required()
                            ->native(false),
                        TextInput::make('source_name')
                            ->label('Nama Provider/Gateway')
                            ->placeholder('digiflazz, vip, tripay, duitku, bangjeff')
                            ->helperText('Gunakan nama provider atau gateway yang sama dengan route callback di aplikasi.')
                            ->required()
                            ->maxLength(255),
                        Select::make('mode')
                            ->label('Mode Whitelist')
                            ->options([
                                'disabled' => 'Nonaktif',
                                'log_only' => 'Pantau Saja',
                                'enforce' => 'Blokir Jika Tidak Cocok',
                            ])
                            ->default('log_only')
                            ->required()
                            ->native(false)
                            ->helperText('Pantau Saja hanya mencatat. Blokir Jika Tidak Cocok akan menolak IP yang tidak ada di daftar.'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                        TextInput::make('description')
                            ->label('Label Singkat')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Catatan')
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
                    ->label('Jenis Callback')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'supplier_callback' => 'Callback Supplier',
                        'payment_gateway' => 'Payment Gateway',
                        default => $state,
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('source_name')
                    ->label('Nama Provider/Gateway')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('mode')
                    ->label('Mode Whitelist')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'disabled' => 'Nonaktif',
                        'log_only' => 'Pantau Saja',
                        'enforce' => 'Blokir Jika Tidak Cocok',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'enforce' => 'danger',
                        'log_only' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Aktif'),
                TextColumn::make('entries_count')
                    ->label('IP Diizinkan')
                    ->counts('entries'),
                TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source_domain')
                    ->label('Jenis Callback')
                    ->options([
                        'supplier_callback' => 'Callback Supplier',
                        'payment_gateway' => 'Payment Gateway',
                    ]),
                SelectFilter::make('mode')
                    ->label('Mode Whitelist')
                    ->options([
                        'disabled' => 'Nonaktif',
                        'log_only' => 'Pantau Saja',
                        'enforce' => 'Blokir Jika Tidak Cocok',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Aktif'),
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
