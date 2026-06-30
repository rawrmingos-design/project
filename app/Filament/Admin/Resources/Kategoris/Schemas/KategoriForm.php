<?php

namespace App\Filament\Admin\Resources\Kategoris\Schemas;

use App\Support\MediaAssetPicker;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;


class KategoriForm
{
    public static function getFormComponents(): array
    {
        return [
            Section::make('Informasi Dasar')
                ->schema([
                    TextInput::make('nama')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Contoh: Mobile Legends')
                    ->live(onBlur: true) 
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('sub_nama', Str::slug($state))),

                TextInput::make('sub_nama')
                    ->label('Sub Nama / Slug')
                    ->required()
                    ->maxLength(225)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($set, ?string $state) => $set('sub_nama', Str::slug($state)))
                    ->dehydrateStateUsing(fn (?string $state): string => Str::slug((string) $state))
                    ->helperText('Otomatis terisi format slug (misal: mobile-legends)'),
                        
                    TextInput::make('kode')
                        ->label('Kode')
                        ->maxLength(255)
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($set, ?string $state) => $set('kode', Str::slug($state)))
                        ->dehydrateStateUsing(fn (?string $state): string => Str::slug((string) $state))
                        ->helperText('Kode unik, contoh: mlbb'),
                        
                    Select::make('tipe')
                        ->label('Tipe')
                        ->options([
                            'game' => 'Game',
                            'voucher' => 'Voucher',
                            'pulsa' => 'Pulsa',
                            'data' => 'Data',
                            'app' => 'App',
                            'vilogml' => 'VilogML',
                            'joki' => 'Joki',
                            'jokigendong' => 'Joki Gendong',
                            'giftskin' => 'Gift Skin',
                            'populer' => 'Populer',
                        ])
                        ->default('game')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?string $state, $set, $get): void {
                            $shouldUseSecondaryField = in_array($state, ['game', 'voucher', 'populer'], true);

                            $field2IsBlank = blank($get('field_2_title'))
                                && blank($get('field_2_placeholder'))
                                && blank($get('field_2_type'))
                                && blank($get('field_select_title_input'))
                                && blank($get('field_select_value_input'));

                            if ($field2IsBlank) {
                                $set('has_field_2', $shouldUseSecondaryField);
                            }
                        })
                        ->helperText('Jenis kategori produk'),

                    Select::make('category_type_id')
                        ->label('Tab Kategori (Category Sequence)')
                        ->relationship('categoryType', 'name')
                        ->searchable()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('sort')
                                ->numeric()
                                ->default(0),
                        ]),
                        
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ])
                        ->default('active')
                        ->required(),
                ])
                ->columns(2),

            Section::make('Custom Input Order')
                ->description('Atur field input yang tampil di halaman order. Field 1 tetap wajib ada dan akan memakai default otomatis berdasarkan tipe kategori jika custom dimatikan.')
                ->schema([
                    Placeholder::make('custom_input_rule_hint')
                        ->label('Panduan')
                        ->content(function (Get $get): HtmlString {
                            $field1Custom = (bool) $get('use_custom_field_1');
                            $field2Enabled = (bool) $get('has_field_2');

                            $message = 'Minimal 1 field selalu aktif. Jika custom Field 1 dimatikan, sistem otomatis memakai default sesuai tipe kategori.';

                            if ($field1Custom && $field2Enabled) {
                                $message = 'Custom Field 1 dan Field 2 aktif.';
                            } elseif ($field1Custom && ! $field2Enabled) {
                                $message = 'Custom Field 1 aktif, Field 2 nonaktif.';
                            } elseif (! $field1Custom && $field2Enabled) {
                                $message = 'Field 1 menggunakan default otomatis, Field 2 aktif.';
                            }

                            return new HtmlString('<span style="font-size:12px;color:#94a3b8;">' . e($message) . '</span>');
                        })
                        ->live()
                        ->columnSpanFull(),

                    Toggle::make('use_custom_field_1')
                        ->label('Aktifkan Custom Field 1')
                        ->live()
                        ->default(false)
                        ->dehydrated(true)
                        ->helperText('Jika nonaktif, sistem pakai default Field 1 sesuai tipe kategori.')
                        ->afterStateUpdated(function (bool $state, $set): void {
                            if ($state) {
                                return;
                            }

                            $set('field_1_title', null);
                            $set('field_1_placeholder', null);
                            $set('field_1_type', 'text');
                        }),

                    TextInput::make('field_1_title')
                        ->label('Label Field 1')
                        ->maxLength(255)
                        ->placeholder('Contoh: User ID')
                        ->helperText('Kalau kosong, akan memakai default sesuai tipe kategori.')
                        ->visible(fn (Get $get) => (bool) $get('use_custom_field_1')),

                    TextInput::make('field_1_placeholder')
                        ->label('Placeholder Field 1')
                        ->maxLength(255)
                        ->placeholder('Contoh: Masukkan User ID')
                        ->visible(fn (Get $get) => (bool) $get('use_custom_field_1')),

                    Select::make('field_1_type')
                        ->label('Tipe Field 1')
                        ->options([
                            'text' => 'Text',
                            'number' => 'Number',
                            'email' => 'Email',
                            'password' => 'Password',
                        ])
                        ->default('text')
                        ->visible(fn (Get $get) => (bool) $get('use_custom_field_1')),

                    Toggle::make('has_field_2')
                        ->label('Aktifkan Field 2')
                        ->live()
                        ->default(false)
                        ->dehydrated(true)
                        ->afterStateUpdated(function (bool $state, $set): void {
                            if ($state) {
                                return;
                            }

                            $set('field_2_title', null);
                            $set('field_2_placeholder', null);
                            $set('field_2_type', 'text');
                            $set('field_select_title_input', null);
                            $set('field_select_value_input', null);
                        }),

                    TextInput::make('field_2_title')
                        ->label('Label Field 2')
                        ->maxLength(255)
                        ->placeholder('Contoh: Server ID')
                        ->visible(fn (Get $get) => (bool) $get('has_field_2')),

                    TextInput::make('field_2_placeholder')
                        ->label('Placeholder Field 2')
                        ->maxLength(255)
                        ->placeholder('Contoh: Masukkan Server ID')
                        ->visible(fn (Get $get) => (bool) $get('has_field_2')),

                    Select::make('field_2_type')
                        ->label('Tipe Field 2')
                        ->options([
                            'text' => 'Text',
                            'number' => 'Number',
                            'password' => 'Password',
                            'select' => 'Select',
                        ])
                        ->default('text')
                        ->visible(fn (Get $get) => (bool) $get('has_field_2')),

                    Textarea::make('field_select_title_input')
                        ->label('Opsi Label Field 2')
                        ->rows(3)
                        ->placeholder("Jawa Barat\nJawa Tengah")
                        ->helperText('Pisahkan dengan enter atau koma.')
                        ->visible(fn (Get $get) => (bool) $get('has_field_2') && $get('field_2_type') === 'select'),

                    Textarea::make('field_select_value_input')
                        ->label('Opsi Value Field 2')
                        ->rows(3)
                        ->placeholder("jabar\njateng")
                        ->helperText('Urutan value harus sama dengan label.')
                        ->visible(fn (Get $get) => (bool) $get('has_field_2') && $get('field_2_type') === 'select'),
                ])
                ->columns(2),

            Section::make('Media')
                ->schema([
                    Grid::make([
                        'default' => 1,
                        'xl' => 2,
                    ])->schema([
                        Section::make('Thumbnail')
                            ->schema([
                                Placeholder::make('thumbnail_current_preview')
                                    ->label('Thumbnail Aktif')
                                    ->content(fn (?Model $record) => MediaAssetPicker::renderCurrentPreview($record, 'thumbnail')),

                                Radio::make('thumbnail_input_mode')
                                    ->label('Sumber Thumbnail')
                                    ->options([
                                        'library' => 'Media Library',
                                        'upload' => 'Upload Baru',
                                    ])
                                    ->default('upload')
                                    ->inline()
                                    ->inlineLabel(false)
                                    ->live()
                                    ->dehydrated(),

                                Hidden::make('thumbnail_media_asset_id')
                                    ->dehydrated(true)
                                    ->afterStateHydrated(function (Hidden $component, $state): void {
                                        if ($state && ! MediaAssetPicker::isUsable($state)) {
                                            $component->state(null);
                                        }
                                    }),

                                Placeholder::make('thumbnail_media_asset_picker')
                                    ->label('Thumbnail dari Media Library')
                                    ->visible(fn (Get $get) => $get('thumbnail_input_mode') === 'library')
                                    ->hintActions([
                                        MediaAssetPicker::makeModalAction(
                                            'chooseKategoriThumbnailMediaAsset',
                                            'thumbnail_media_asset_id',
                                            'Pilih Thumbnail dari Media Library',
                                            ['kategori', 'logo', 'lainnya'],
                                            'kategori',
                                        ),
                                        MediaAssetPicker::makeClearAction(
                                            'clearKategoriThumbnailMediaAsset',
                                            'thumbnail_media_asset_id',
                                        ),
                                    ])
                                    ->content(fn (Get $get, ?Model $record) => MediaAssetPicker::renderSelectedOrCurrentPreview(
                                        $get('thumbnail_media_asset_id'),
                                        $record,
                                        'thumbnail',
                                    )),

                                SpatieMediaLibraryFileUpload::make('thumbnail')
                                    ->label('Thumbnail')
                                    ->image()
                                    ->disk('assets')
                                    ->visibility('public')
                                    ->collection('thumbnail')
                                    ->visible(fn (Get $get) => $get('thumbnail_input_mode') === 'upload')
                                    ->imagePreviewHeight('150')
                                    ->panelAspectRatio('1:1')
                                    ->panelLayout('integrated')
                                    ->removeUploadedFileButtonPosition('right')
                                    ->uploadButtonPosition('left')
                                    ->helperText('Upload baru otomatis disimpan ke Media Library dan tetap sinkron ke path thumbnail lama.')
                                    ->required(),
                            ]),

                        Section::make('Banner')
                            ->schema([
                                Placeholder::make('banner_current_preview')
                                    ->label('Banner Aktif')
                                    ->content(fn (?Model $record) => MediaAssetPicker::renderCurrentPreview($record, 'banner')),

                                Radio::make('banner_input_mode')
                                    ->label('Sumber Banner')
                                    ->options([
                                        'library' => 'Media Library',
                                        'upload' => 'Upload Baru',
                                    ])
                                    ->default('upload')
                                    ->inline()
                                    ->inlineLabel(false)
                                    ->live()
                                    ->dehydrated(),

                                Hidden::make('banner_media_asset_id')
                                    ->dehydrated(true)
                                    ->afterStateHydrated(function (Hidden $component, $state): void {
                                        if ($state && ! MediaAssetPicker::isUsable($state)) {
                                            $component->state(null);
                                        }
                                    }),

                                Placeholder::make('banner_media_asset_picker')
                                    ->label('Banner dari Media Library')
                                    ->visible(fn (Get $get) => $get('banner_input_mode') === 'library')
                                    ->hintActions([
                                        MediaAssetPicker::makeModalAction(
                                            'chooseKategoriBannerMediaAsset',
                                            'banner_media_asset_id',
                                            'Pilih Banner dari Media Library',
                                            ['banner', 'kategori', 'lainnya'],
                                            'banner',
                                        ),
                                        MediaAssetPicker::makeClearAction(
                                            'clearKategoriBannerMediaAsset',
                                            'banner_media_asset_id',
                                        ),
                                    ])
                                    ->content(fn (Get $get, ?Model $record) => MediaAssetPicker::renderSelectedOrCurrentPreview(
                                        $get('banner_media_asset_id'),
                                        $record,
                                        'banner',
                                    )),

                                SpatieMediaLibraryFileUpload::make('banner')
                                    ->label('Banner')
                                    ->image()
                                    ->disk('assets')
                                    ->visibility('public')
                                    ->collection('banner')
                                    ->visible(fn (Get $get) => $get('banner_input_mode') === 'upload')
                                    ->imagePreviewHeight('150')
                                    ->panelAspectRatio('3:1')
                                    ->panelLayout('integrated')
                                    ->removeUploadedFileButtonPosition('right')
                                    ->uploadButtonPosition('left')
                                    ->helperText('Upload baru otomatis disimpan ke Media Library dan tetap sinkron ke path banner lama.'),
                            ]),
                    ]),
                ]),
                
            Section::make('Konfigurasi')
                ->schema([
                    Toggle::make('server_id')
                        ->label('Memerlukan Server ID')
                        ->default(false),

                    Toggle::make('require_user_id')
                        ->label('Wajib Mengisi User ID')
                        ->helperText('Nonaktifkan jika produk tidak memerlukan ID game pelanggan. Contoh: Roblox (kode redeem), Google Play Gift Card.')
                        ->default(true),
                ]),
                
            Section::make('Deskripsi')
                ->schema([
                    RichEditor::make('deskripsi_game')
                        ->label('Deskripsi Game')
                        ->required(),
                        
                    RichEditor::make('deskripsi_field')
                        ->label('Deskripsi Field')
                        ->required(),
                ])
                ->columns(1),

            Section::make('Advanced SEO Configuration')
                ->description('Optimasi SEO manual untuk halaman games ini. Kosongkan jika ingin auto-generated.')
                ->schema([
                    TextInput::make('meta_title')
                        ->label('Meta Title')
                        ->placeholder('Ex: Top Up Mobile Legends Murah & Cepat - ' . config('app.name'))
                        ->maxLength(255),
                        
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->placeholder('Ex: Beli Diamond MLBB termurah dengan proses instan 24 jam...')
                        ->rows(3),
                        
                    Textarea::make('schema_markup')
                        ->label('Custom Schema Markup (JSON-LD)')
                        ->placeholder('<script type="application/ld+json">{ ... }</script>')
                        ->rows(5)
                        ->helperText('Pastikan format JSON valid. Sertakan tag <script> jika perlu.'),
                ])
                ->collapsible(),
        ];
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(static::getFormComponents());
    }
}
