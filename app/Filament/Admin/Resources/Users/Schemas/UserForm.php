<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                            
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                            
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                            
                        TextInput::make('no_wa')
                            ->label('No. WhatsApp')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('08123456789'),
                    ])
                    ->columnSpan(2),
                    
                Section::make('Role & Tier Management')
                    ->schema([
                        Select::make('role')
                            ->label('Role/Tier')
                            ->options([
                                'Admin' => 'Admin',
                                'Platinum' => 'Platinum Member',
                                'Gold' => 'Gold Member',
                                'Member' => 'Regular Member',
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // Auto-set balance based on tier
                                $defaultBalances = [
                                    'Admin' => 1000000,
                                    'Platinum' => 100000,
                                    'Gold' => 50000,
                                    'Member' => 0,
                                ];
                                
                                if (isset($defaultBalances[$state])) {
                                    $set('balance', $defaultBalances[$state]);
                                }
                            }),
                            
                        Placeholder::make('tier_benefits')
                            ->label('Tier Benefits')
                            ->content(function ($get) {
                                $role = $get('role');
                                return match($role) {
                                    'Admin' => '• Full system access
• Unlimited transactions
• All features unlocked',
                                    'Platinum' => '• Premium pricing
• Priority support
• Advanced features
• Higher transaction limits',
                                    'Gold' => '• Better pricing
• Priority queue
• Extended features',
                                    'Member' => '• Standard pricing
• Basic features
• Regular support',
                                    default => 'Select a role to see benefits'
                                };
                            })
                            ->visible(fn ($get) => $get('role') !== null),
                    ])
                    ->columnSpan(1),
                    
                Section::make('Security & Authentication')
                    ->schema([
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->revealable(),
                            
                        TextInput::make('api_key')
                            ->label('API Key')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on save'),
                            
                        Actions::make([
                            Action::make('generate_api_key')
                                ->label('Generate API Key')
                                ->icon('heroicon-o-key')
                                ->color('warning')
                                ->action(function ($set) {
                                    $apiKey = 'tk_' . bin2hex(random_bytes(16));
                                    $set('api_key', $apiKey);
                                })
                                ->visible(fn (string $operation) => $operation === 'edit'),
                        ]),
                    ])
                    ->columnSpan(2),
                    
                Section::make('Balance & Gaming Info')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('balance')
                                    ->label('Saldo')
                                    ->numeric()
                                    ->default(0)
                                    ->prefix('Rp')
                                    ->step(1000),
                                    
                                Placeholder::make('formatted_balance')
                                    ->label('Formatted Balance')
                                    ->content(fn ($get) => 'Rp ' . number_format($get('balance') ?? 0, 0, ',', '.'))
                                    ->visible(fn ($get) => $get('balance') !== null),
                            ]),
                            
                        Grid::make(3)
                            ->schema([
                                TextInput::make('idgame')
                                    ->label('Game ID 1')
                                    ->maxLength(225),
                                    
                                TextInput::make('servergame')
                                    ->label('Server Game')
                                    ->numeric(),
                                    
                                TextInput::make('idgame2')
                                    ->label('Game ID 2')
                                    ->maxLength(2225),
                            ]),
                    ])
                    ->columnSpan(3)
                    ->collapsible(),
                    
                Section::make('Security Features')
                    ->schema([
                        TextInput::make('otp')
                            ->label('OTP Code')
                            ->maxLength(255)
                            ->disabled()
                            ->placeholder('System generated'),
                            
                        TextInput::make('google2fa_secret')
                            ->label('Google 2FA Secret')
                            ->maxLength(2255)
                            ->disabled()
                            ->placeholder('Generated when 2FA is enabled'),
                    ])
                    ->columnSpan(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
