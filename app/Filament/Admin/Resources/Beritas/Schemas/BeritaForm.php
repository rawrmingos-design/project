<?php

namespace App\Filament\Admin\Resources\Beritas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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

                        FileUpload::make('path')
                            ->label('Image')
                            ->image()
                            ->disk('assets')
                            ->directory('assets/banner')
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
