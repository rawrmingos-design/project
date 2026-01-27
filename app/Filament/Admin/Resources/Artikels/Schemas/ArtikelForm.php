<?php

namespace App\Filament\Admin\Resources\Artikels\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Str;

class ArtikelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten Artikel')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Artikel')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($set, $state) => $set('slug', Str::slug($state)))
                            ->columnSpanFull()
                            ->helperText('Contoh: Cara Top Up Mobile Legends Murah'),
                            
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull()
                            ->helperText('URL friendly text (otomatis dari judul)'),
                            
                        FileUpload::make('thumbnail')
                            ->label('Thumbnail')
                            ->image()
                            ->disk('assets')
                            ->directory('articles/thumbnails')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull()
                            ->helperText('Gambar utama artikel (Max 2MB)'),
                            
                        RichEditor::make('content')
                            ->label('Isi Artikel')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'strike', 'link',
                                'bulletList', 'orderedList', 'h2', 'h3',
                                'blockquote', 'codeBlock', 'undo', 'redo'
                            ])
                            ->columnSpanFull() 
                            ->required()
                            ->helperText('Tulis artikel lengkap di sini'),
                            
                        Select::make('status')
                            ->label('Status Publikasi')
                            ->options([
                                'active' => 'Active (Published)',
                                'inactive' => 'Inactive (Draft)',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
                    
                Section::make('Tampilan & Layout')
                    ->description('Kustomisasi tampilan artikel ini di halaman frontend.')
                    ->schema([
                        Select::make('layout')
                            ->label('Template Layout')
                            ->options([
                                'default' => 'Default (Classic)',
                                'modern' => 'Modern (Full Header)',
                            ])
                            ->default('default')
                            ->required(),
                        
                        ColorPicker::make('primary_color')
                            ->label('Warna Utama')
                            ->helperText('Warna untuk judul, tombol, dan aksen utama.'),

                        ColorPicker::make('secondary_color')
                            ->label('Warna Sekunder')
                            ->helperText('Warna untuk background aksen atau elemen dekoratif.'),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('SEO Configuration')
                    ->schema([
                        Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText('Deskripsi singkat untuk hasil pencarian Google (Max 160 karakter)'),
                            
                        TextInput::make('keywords')
                            ->label('Keywords')
                            ->helperText('Kata kunci dipisahkan koma. Contoh: topup, mlbb, murah'),
                    ])
                    ->collapsible(),
            ]);
    }
}
