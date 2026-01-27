<?php

namespace App\Filament\Admin\Resources\Pakets\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LayananRelationManager extends RelationManager
{
    protected static string $relationship = 'layanan';

    protected static ?string $recordTitleAttribute = 'layanan';
    
    protected static ?string $title = 'Layanan Terkait';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('layanan')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('layanan')
                    ->label('Nama Layanan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('harga')
                    ->label('Harga Dari Provider')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['layanan', 'provider'])
                    ->modalHeading('Tambah Layanan ke Paket'),
            ])
            ->actions([
                Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
