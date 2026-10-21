<?php

namespace App\Filament\Admin\Resources\Vouchers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Services\Get;
use Illuminate\Validation\Rules\Unique;

class VoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Voucher Information')
                    ->columns(2)
                    ->schema([
                        // Pemilik voucher menentukan cakupannya:
                        // kosong = platform (berlaku di semua toko),
                        // terisi = hanya toko tenant itu.
                        Select::make('tenant_id')
                            ->label('Pemilik')
                            ->relationship('tenant', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Platform (berlaku di semua toko)')
                            ->helperText('Kosongkan untuk voucher platform. Pilih tenant supaya kode hanya berlaku di tokonya.')
                            ->columnSpanFull(),

                        TextInput::make('kode')
                            ->label('Voucher Code')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., DISCOUNT50')
                            // Keunikan per cakupan: dua tenant boleh memakai kode
                            // yang sama; dalam satu cakupan harus unik. Tanpa
                            // where(), validasi ini menolak kode yang sah.
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get) => $rule
                                    ->where(fn ($query) => $get('tenant_id')
                                        ? $query->where('tenant_id', $get('tenant_id'))
                                        : $query->whereNull('tenant_id'))
                            )
                            ->helperText('Kode unik dalam cakupan pemiliknya'),

                        Select::make('layanan_id')
                            ->label('Produk')
                            ->relationship('layanan', 'layanan')
                            ->searchable()
                            ->preload()
                            ->placeholder('Semua produk')
                            ->helperText('Kosongkan untuk semua produk. Isi untuk membatasi ke satu produk.')
                            ->columnSpanFull(),

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
                    ->columns(2)
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

                        DateTimePicker::make('expired_at')
                            ->label('Expired At')
                            ->seconds(false)
                            ->native(false)
                            ->helperText('Leave empty if this voucher does not expire'),
                    ])
                    ->collapsible(),
            ]);
    }
}
