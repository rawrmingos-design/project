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
                Section::make('Informasi User')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama lengkap user/member.'),
                            
                        TextInput::make('username')
                            ->label('Username')
                            ->unique(ignoreRecord: true)
                            ->default('Anonim')
                            ->maxLength(255)
                            ->helperText('Dipakai untuk login atau identitas akun.'),
                            
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                            
                        TextInput::make('no_wa')
                            ->label('No. WhatsApp')
                            ->tel()
                            ->placeholder('+62812345678')
                            ->maxLength(255)
                            ->helperText('Gunakan format +628...'),
                            
                        Select::make('role')
                            ->label('Role User')
                            ->options([
                                'Admin' => 'Admin',
                                'Member' => 'Member',
                                'Gold' => 'Gold Member',
                                'Platinum' => 'Platinum Member',
                            ])
                            ->required()
                            ->default('Member')
                            ->native(false)
                            ->helperText('Tingkatan akun user.'),
                            
                        TextInput::make('balance')
                            ->label('Saldo Akun')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->collapsible(),
                    
                Section::make('Keamanan Akun')
                    ->columns(2)
                    ->schema([
                        TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => !empty($state))
                            ->label('Password')
                            ->helperText('Kosongkan jika tidak ingin mengubah password.'),
                            
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Dibuat otomatis untuk akses API.'),
                    ])
                    ->collapsible(),
                    
                Section::make('Informasi Game (Opsional)')
                    ->columns(3)
                    ->schema([
                        TextInput::make('idgame')
                            ->label('ID Game')
                            ->maxLength(225),
                            
                        TextInput::make('servergame')
                            ->label('Server Game')
                            ->numeric(),
                            
                        TextInput::make('idgame2')
                            ->label('ID Game 2')
                            ->maxLength(2225),
                    ])
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}
