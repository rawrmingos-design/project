<?php

namespace App\Filament\Admin\Resources\CategoryTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CategoryTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->helperText('Contoh: Games, Voucher, Pulsa'),
                TextInput::make('slug')
                    ->required()
                    ->helperText('Contoh: games (huruf kecil, tanpa spasi)'),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Urutan tampilan di halaman depan (1, 2, 3...)'),
                TextInput::make('icon')
                    ->helperText('Logo/Icon kategori (opsional)'),
            ]);
    }
}
