<?php

namespace App\Filament\Admin\Resources\Beritas\Schemas;

use App\Support\MediaAssetPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class BeritaForm
{

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Banner/Popup Configuration')
                    ->schema([
                        Select::make('tipe')
                            ->label('Type')
                            ->options([
                                'banner' => 'Banner',
                                'popup' => 'Popup',
                            ])
                            ->required()
                            ->default('banner')
                            ->native(false)
                            ->helperText('Choose display type'),

                        TextInput::make('urutan')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required()
                            ->helperText('Semakin kecil angka, semakin dulu ditampilkan.'),

                        Placeholder::make('path_current_preview')
                            ->label('Gambar Aktif')
                            ->content(fn (?Model $record) => MediaAssetPicker::renderCurrentPreview($record, null, 'path')),

                        Radio::make('path_input_mode')
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

                        Hidden::make('path_media_asset_id')
                            ->dehydrated(true)
                            ->afterStateHydrated(function (Hidden $component, $state): void {
                                if ($state && ! MediaAssetPicker::isUsable($state)) {
                                    $component->state(null);
                                }
                            }),

                        Placeholder::make('path_media_asset_picker')
                            ->label('Gambar dari Media Library')
                            ->visible(fn (Get $get) => $get('path_input_mode') === 'library')
                            ->hintActions([
                                MediaAssetPicker::makeModalAction(
                                    'chooseBeritaMediaAsset',
                                    'path_media_asset_id',
                                    'Pilih Gambar dari Media Library',
                                    ['banner', 'lainnya'],
                                    'banner',
                                ),
                                MediaAssetPicker::makeClearAction(
                                    'clearBeritaMediaAsset',
                                    'path_media_asset_id',
                                ),
                            ])
                            ->content(fn (Get $get, ?Model $record) => MediaAssetPicker::renderSelectedOrCurrentPreview(
                                $get('path_media_asset_id'),
                                $record,
                                null,
                                'path',
                            )),

                        FileUpload::make('path')
                            ->label('Image')
                            ->image()
                            ->disk(config('uploads.disk', 'assets'))
                            ->directory('assets/banner')
                            ->visible(fn (Get $get) => $get('path_input_mode') === 'upload')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->imagePreviewHeight('200')
                            ->panelAspectRatio('16:9')
                            ->panelLayout('integrated')
                            ->uploadButtonPosition('left')
                            ->removeUploadedFileButtonPosition('right')
                            ->required()
                            ->helperText('Upload banner or popup image (max 2MB). Files saved to /banner/')
                            ->downloadable()
                            ->openable()
                            ->preserveFilenames(),
                            
                        RichEditor::make('deskripsi')
                            ->label('Description')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'link',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'codeBlock',
                            ])
                            ->columnSpanFull() 
                            ->helperText('Rich text description or content'),
                    ])
                    ->collapsible(),
            ]);
    }
}
