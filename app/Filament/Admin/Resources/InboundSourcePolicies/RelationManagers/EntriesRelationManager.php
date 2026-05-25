<?php

namespace App\Filament\Admin\Resources\InboundSourcePolicies\RelationManagers;

use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    protected static ?string $recordTitleAttribute = 'value';

    protected static ?string $title = 'Allowed IP / CIDR Entries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('value')
                    ->label('IP or CIDR')
                    ->placeholder('203.0.113.10 or 203.0.113.0/24')
                    ->required()
                    ->maxLength(255),
                Select::make('value_type')
                    ->label('Value Type')
                    ->options([
                        'ipv4' => 'IPv4',
                        'ipv6' => 'IPv6',
                        'cidr_ipv4' => 'CIDR IPv4',
                        'cidr_ipv6' => 'CIDR IPv6',
                    ])
                    ->default('ipv4')
                    ->required()
                    ->native(false),
                TextInput::make('label')
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
                DateTimePicker::make('last_verified_at')
                    ->label('Last Verified At')
                    ->seconds(false),
                Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value')
                    ->label('IP / CIDR')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('value_type')
                    ->badge(),
                TextColumn::make('label')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('last_verified_at')
                    ->label('Verified')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
