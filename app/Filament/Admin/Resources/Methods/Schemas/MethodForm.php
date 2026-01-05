<?php

namespace App\Filament\Admin\Resources\Methods\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;



class MethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Metode Pembayaran')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Metode')
                            ->required()
                            ->maxLength(55),
                            
                        TextInput::make('code')
                            ->label('Kode')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),
                            
                        Select::make('tipe')
                            ->label('Tipe')
                            ->options([
                                'bank' => 'Bank Transfer',
                                'ewallet' => 'E-Wallet',
                                'qris' => 'QRIS',
                                'virtual-account' => 'Virtual Account',
                                'convenience-store' => 'Convenience Store',
                                'SALDO' => 'Saldo',
                            ])
                            ->required(),
                            
                        Select::make('payment')
                            ->label('Payment Gateway')
                            ->options([
                                'tripay' => 'Tripay',
                                'tokopay' => 'Tokopay',
                                'paydisini' => 'Paydisini',
                                'manual' => 'Manual',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                    
                Section::make('Biaya & Limit')
                    ->schema([
                        TextInput::make('fee_percent')
                            ->label('Fee Persentase')
                            ->numeric()
                            ->step(0.01)
                            ->suffix('%')
                            ->default(0),
                            
                        TextInput::make('fix_fee')
                            ->label('Fix Fee')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('Rp')
                            ->default(0),
                            
                        TextInput::make('min_pembelian')
                            ->label('Minimum Pembelian')
                            ->numeric()
                            ->prefix('Rp'),
                            
                        TextInput::make('max_pembelian')
                            ->label('Maximum Pembelian')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->columns(2),
                    
                Section::make('Media & Status')
                    ->schema([
                        FileUpload::make('images')
                            ->label('Logo/Icon')
                            ->image()
                            ->validationMessages([
                                'mimetypes' => 'Format file harus JPG, PNG, atau WEBP.',
                            ])
                            ->disk('assets')
                            ->directory('assets/payment')
                            ->visibility('public')
                            ->imagePreviewHeight('150')
                            ->loadingIndicatorPosition('left')
                            ->panelAspectRatio('2:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left'),
                            
                        Toggle::make('statuspayment')
                            ->label('Status Aktif')
                            ->default(true),
                            
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->maxLength(250),
                    ])
                    ->columns(1),
            ]);
    }
}
