<?php

namespace App\Filament\Admin\Resources\Ratings\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;

class RatingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Ulasan')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username Pembeli')
                            ->required()
                            ->maxLength(225),
                            
                        TextInput::make('layanan')
                            ->label('Layanan')
                            ->required()
                            ->maxLength(225),
                            
                        Select::make('bintang')
                            ->label('Rating Bintang')
                            ->options([
                                '1' => '⭐️ 1 (Buruk)',
                                '2' => '⭐️⭐️ 2 (Kurang)',
                                '3' => '⭐️⭐️⭐️ 3 (Cukup)',
                                '4' => '⭐️⭐️⭐️⭐️ 4 (Baik)',
                                '5' => '⭐️⭐️⭐️⭐️⭐️ 5 (Sempurna)',
                            ])
                            ->required(),
                            
                        Textarea::make('comment')
                            ->label('Komentar')
                            ->rows(3)
                            ->required(),
                            
                        TextInput::make('no_pembeli')
                            ->label('No. WhatsApp / Kontak')
                            ->tel()
                            ->required()
                            ->maxLength(225),
                            
                        // Hidden fields or auto-generated if needed
                        TextInput::make('rating_id')
                            ->label('Rating ID (Auto)')
                            ->default(fn () => 'RTG-' . strtoupper(uniqid()))
                            ->readOnly()
                            ->required(),
                    ])
            ]);
    }
}
