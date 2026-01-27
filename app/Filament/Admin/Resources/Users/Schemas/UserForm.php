<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama lengkap user'),
                            
                        TextInput::make('username')
                            ->label('Username')
                            ->unique(ignoreRecord: true)
                            ->default('anonim')
                            ->maxLength(255)
                            ->helperText('Digunakan untuk login'),
                            
                        TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                            
                        TextInput::make('no_wa')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->placeholder('+62812345678')
                            ->maxLength(255)
                            ->helperText('Format: +628...'),
                            
                        Select::make('role')
                            ->label('User Role')
                            ->options([
                                'Admin' => 'Admin',
                                'Member' => 'Member',
                                'Gold' => 'Gold Member',
                                'Platinum' => 'Platinum Member',
                            ])
                            ->required()
                            ->default('Member')
                            ->native(false)
                            ->helperText('Tingkatan level user'),
                            
                        TextInput::make('balance')
                            ->label('Account Balance')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->collapsible(),
                    
                Section::make('Security')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => !empty($state))
                            ->label('Password')
                            ->helperText('Leave blank to keep current password'),
                            
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Auto-generated for API access'),
                    ])
                    ->collapsible(),
                    
                Section::make('Game Information (Optional)')
                    ->columns(3)
                    ->schema([
                        TextInput::make('idgame')
                            ->label('Game ID')
                            ->maxLength(225),
                            
                        TextInput::make('servergame')
                            ->label('Server Game')
                            ->numeric(),
                            
                        TextInput::make('idgame2')
                            ->label('Game ID 2')
                            ->maxLength(2225),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}
