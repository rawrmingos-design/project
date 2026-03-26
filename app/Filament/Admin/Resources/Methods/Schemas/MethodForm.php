<?php

namespace App\Filament\Admin\Resources\Methods\Schemas;

use App\Support\MediaAssetPicker;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

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
                                'e-walet' => 'E-Wallet',
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
                                'duitku' => 'Duitku',
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
                        Placeholder::make('images_current_preview')
                            ->label('Logo Aktif')
                            ->content(fn (?Model $record) => MediaAssetPicker::renderCurrentPreview($record, null, 'images')),

                        Radio::make('images_input_mode')
                            ->label('Sumber Gambar')
                            ->options([
                                'library' => 'Media Library',
                                'upload' => 'Upload Baru',
                            ])
                            ->default('upload')
                            ->inline()
                            ->inlineLabel(false)
                            ->live()
                            ->dehydrated(),

                        Hidden::make('images_media_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('images_media_asset_picker')
                            ->label('Logo dari Media Library')
                            ->visible(fn (Get $get) => $get('images_input_mode') === 'library')
                            ->content(fn (Get $get, ?Model $record) => MediaAssetPicker::renderSelectedOrCurrentPreview(
                                $get('images_media_asset_id'),
                                $record,
                                null,
                                'images',
                            ))
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseMethodImageMediaAsset',
                                    'images_media_asset_id',
                                    'Pilih Logo dari Media Library',
                                    ['logo', 'lainnya', 'banner', 'produk'],
                                    'logo',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearMethodImageMediaAsset',
                                    'images_media_asset_id',
                                ),
                            ]),

                        FileUpload::make('images')
                            ->label('Logo/Icon')
                            ->image()
                            ->validationMessages([
                                'mimetypes' => 'Format file harus JPG, PNG, atau WEBP.',
                            ])
                            ->disk('assets')
                            ->directory('assets/thumbnail')
                            ->visibility('public')
                            ->visible(fn (Get $get) => $get('images_input_mode') === 'upload')
                            ->required(fn (Get $get, ?Model $record) => $get('images_input_mode') === 'upload' && ! $record)
                            ->imagePreviewHeight('150')
                            ->loadingIndicatorPosition('left')
                            ->panelAspectRatio('2:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->uploadProgressIndicatorPosition('left')
                            ->helperText('Upload baru akan disimpan ke path metode pembayaran. Kamu juga bisa pilih asset reusable dari Media Library.'),

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
