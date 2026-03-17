<?php

namespace App\Filament\Admin\Resources\MediaAssets\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class MediaAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Media Asset')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Asset')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama internal agar asset mudah dicari ulang.'),

                        Select::make('folder')
                            ->label('Folder')
                            ->options([
                                'produk' => 'Produk',
                                'kategori' => 'Kategori',
                                'banner' => 'Banner',
                                'artikel' => 'Artikel',
                                'logo' => 'Logo',
                                'lainnya' => 'Lainnya',
                            ])
                            ->searchable()
                            ->placeholder('Pilih folder logis'),

                        TextInput::make('alt_text')
                            ->label('Alt Text')
                            ->maxLength(255)
                            ->helperText('Opsional, untuk aksesibilitas dan SEO.')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Placeholder::make('current_file_preview')
                            ->label('Preview File Saat Ini')
                            ->content(function ($record) {
                                if (! $record?->file_url) {
                                    return new HtmlString('<span class="text-sm text-gray-400">Belum ada file valid yang bisa dipreview.</span>');
                                }

                                $url = e($record->file_url);
                                $alt = e($record->alt_text ?: $record->name);
                                $path = e($record->resolveRelativePath() ?: '-');

                                return new HtmlString(<<<HTML
<div style="display:flex;flex-direction:column;gap:10px;">
    <img src="{$url}" alt="{$alt}" style="max-width:220px;max-height:220px;object-fit:cover;border-radius:14px;border:1px solid rgba(148,163,184,.2);" />
    <span style="font-size:12px;color:#94a3b8;">{$path}</span>
</div>
HTML);
                            })
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('file')
                            ->label('File')
                            ->collection('file')
                            ->disk('assets')
                            ->image()
                            ->visibility('public')
                            ->imagePreviewHeight('220')
                            ->panelAspectRatio('1:1')
                            ->panelLayout('integrated')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->required(fn ($record): bool => blank($record?->file_url))
                            ->helperText('Kalau asset ini berasal dari folder yang sudah di-index, preview tampil di atas. Upload file baru hanya jika memang ingin mengganti filenya.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
