<?php

namespace App\Filament\Admin\Resources\TierPricing\Schemas;

use App\Models\TierPricing;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class TierPricingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Paket')
                    ->schema([
                        TextInput::make('tier')
                            ->label('Kode Tier')
                            ->required()
                            ->maxLength(32)
                            ->unique(ignoreRecord: true)
                            ->helperText('Kode internal: starter / business / enterprise. Jangan diubah setelah dipakai tenant.')
                            ->disabledOn('edit'),

                        TextInput::make('label')
                            ->label('Nama Tampilan')
                            ->maxLength(64)
                            ->helperText('Nama di kartu harga. Kosongkan untuk memakai kode tier.'),

                        TextInput::make('badge')
                            ->label('Badge')
                            ->maxLength(32)
                            ->helperText('Contoh: Popular. Kosongkan kalau tidak perlu.'),

                        TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_active')
                            ->label('Aktif untuk pendaftaran')
                            ->default(true)
                            ->helperText('Matikan untuk menyembunyikan paket dari halaman pendaftaran.'),
                    ])
                    ->columns(2),

                Section::make('Markup Harga')
                    ->description('Markup platform menentukan harga beli tenant. Harga jual ke customer tidak terpengaruh markup platform.')
                    ->schema([
                        TextInput::make('platform_markup_percent')
                            ->label('Markup Platform (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('Diterapkan ke layanans.harga seluruh katalog. Contoh: 4% berarti tenant beli 4% di atas harga pokok platform.'),

                        TextInput::make('tenant_markup_default')
                            ->label('Default Markup Tenant (%)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->maxValue(1000)
                            ->step(0.01)
                            ->suffix('%')
                            ->rules([
                                fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    $platform = (float) $get('platform_markup_percent');

                                    if ((float) $value <= $platform) {
                                        $fail('Markup tenant harus lebih besar dari markup platform ('
                                            . rtrim(rtrim(number_format($platform, 2, ',', '.'), '0'), ',')
                                            . '%). Kalau tidak, tenant menjual di bawah modalnya sendiri.');
                                    }
                                },
                            ])
                            ->helperText('Dipakai saat tenant baru belum mengatur markup sendiri. Wajib > markup platform.'),
                    ])
                    ->columns(2),

                Section::make('Harga Paket')
                    ->description('Hanya dua durasi: 3 bulan dan 12 bulan. Harga coret dihitung otomatis dari persen potongan.')
                    ->schema([
                        TextInput::make('price_3_month')
                            ->label('Harga 3 Bulan (Rp)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('Rp'),

                        TextInput::make('strike_discount_3_month')
                            ->label('Potongan Tampilan 3 Bulan (%)')
                            ->numeric()
                            ->default(20)
                            ->minValue(0)
                            ->maxValue(99)
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('Harga coret = harga ÷ (1 − potongan). Kosongkan/0 untuk tidak menampilkan harga coret.')
                            ->live(onBlur: true),

                        TextInput::make('price_12_month')
                            ->label('Harga 12 Bulan (Rp)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('Rp'),

                        TextInput::make('strike_discount_12_month')
                            ->label('Potongan Tampilan 12 Bulan (%)')
                            ->numeric()
                            ->default(40)
                            ->minValue(0)
                            ->maxValue(99)
                            ->step(0.01)
                            ->suffix('%')
                            ->helperText('Harga coret = harga ÷ (1 − potongan). Kosongkan/0 untuk tidak menampilkan harga coret.')
                            ->live(onBlur: true),
                    ])
                    ->columns(2),

                Section::make('Domain')
                    ->schema([
                        TagsInput::make('domain_tlds')
                            ->label('TLD Gratis (paket 12 bulan)')
                            ->helperText('Tekan Enter untuk menambah. Contoh: com, my.id, biz.id')
                            ->placeholder('com'),

                        TagsInput::make('domain_paid_tlds')
                            ->label('TLD Bayar Tambahan')
                            ->helperText('TLD yang tersedia tapi berbayar di luar paket.')
                            ->placeholder('id'),
                    ])
                    ->columns(2),

                Section::make('Fitur di Kartu Harga')
                    ->schema([
                        TagsInput::make('features')
                            ->label('Daftar Fitur')
                            ->helperText('Satu baris = satu fitur di kartu harga halaman pendaftaran. Bisa diubah tanpa deploy.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
