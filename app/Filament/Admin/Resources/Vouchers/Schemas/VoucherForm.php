<?php

namespace App\Filament\Admin\Resources\Vouchers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Voucher Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('kode')
                            ->label('Voucher Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., DISCOUNT50')
                            ->helperText('Unique voucher code for users to redeem'),
                            
                        TextInput::make('promo')
                            ->label('Discount Percentage')
                            ->required()
                            ->numeric()
                            ->suffix('%')
                            ->minValue(1)
                            ->maxValue(100)
                            ->helperText('Percentage discount (1-100%)'),
                    ])
                    ->collapsible(),
                    
                Section::make('Voucher Settings')
                    ->columns(3)
                    ->schema([
                        TextInput::make('stock')
                            ->label('Available Stock')
                            ->required()
                            ->numeric()
                            ->default(100)
                            ->minValue(0)
                            ->helperText('Number of times this voucher can be used'),
                            
                        TextInput::make('mintrx')
                            ->label('Minimum Transaction')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(10000)
                            ->minValue(0)
                            ->helperText('Minimum transaction amount to use this voucher'),
                            
                        TextInput::make('max_potongan')
                            ->label('Maximum Discount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(50000)
                            ->minValue(0)
                            ->helperText('Maximum discount amount (cap)'),
                    ])
                    ->collapsible(),
            ]);
    }
}
