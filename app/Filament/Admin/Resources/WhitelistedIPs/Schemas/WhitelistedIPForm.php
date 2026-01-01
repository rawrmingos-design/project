<?php

namespace App\Filament\Admin\Resources\WhitelistedIPs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class WhitelistedIPForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Whitelist IP Address')
                    ->description('Add IP address to whitelist for API access')
                    ->schema([
                        TextInput::make('ip_address')
                            ->label('IP Address')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., 192.168.1.1')
                            ->helperText('Enter the IP address to whitelist. This IP will be allowed to access the API.')
                            ->maxLength(225)
                            ->rules(['ip']),
                    ]),
            ]);
    }
}
