<?php

namespace App\Filament\Admin\Resources\Pakets\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use App\Models\Kategori;

class PaketForm
{
    public static function getFormComponents(): array
    {
        return [
            Section::make('Informasi Paket')
                ->schema([
                    TextInput::make('nama')
                        ->label('Nama Paket')
                        ->required()
                        ->maxLength(255),
                ]),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormComponents());
    }
}
