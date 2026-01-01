<?php

namespace App\Filament\Admin\Resources\Deposits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

class DepositForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Deposit Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('order_id')
                            ->label('Order ID')
                            ->required()
                            ->default(fn () => 'DEP-' . strtoupper(Str::random(10)))
                            ->disabled()
                            ->dehydrated()
                            ->maxLength(255),
                            
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(255)
                            ->helperText('User account username'),
                            
                        Select::make('metode')
                            ->label('Payment Method')
                            ->options([
                                'BCA' => 'BCA',
                                'BNI' => 'BNI',
                                'BRI' => 'BRI',
                                'Mandiri' => 'Mandiri',
                                'QRIS' => 'QRIS',
                                'OVO' => 'OVO',
                                'DANA' => 'DANA',
                                'GoPay' => 'GoPay',
                                'ShopeePay' => 'ShopeePay',
                            ])
                            ->required()
                            ->native(false)
                            ->searchable(),
                            
                        TextInput::make('no_pembayaran')
                            ->label('Payment Number/Reference')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Account number, phone number'),
                            
                        TextInput::make('jumlah')
                            ->label('Amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(10000)
                            ->step(1000)
                            ->helperText('Minimum: Rp 10.000'),
                            
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'Pending' => 'Pending',
                                'Success' => 'Success',
                            ])
                            ->required()
                            ->default('Pending')
                            ->native(false),
                    ])
                    ->collapsible(),
            ]);
    }
}
