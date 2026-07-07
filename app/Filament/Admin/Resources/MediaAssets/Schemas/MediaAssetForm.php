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
                Section::make('File Manager')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama File')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nama internal agar file mudah dicari ulang.'),

                        Select::make('folder')
                            ->label('Folder')
                            ->options([
                                'produk' => 'Produk',
                                'kategori' => 'Kategori',
                                'banner' => 'Banner',
                                'artikel' => 'Artikel',
                                'logo' => 'Logo',
                                'seasonal' => 'Seasonal',
                                'dokumen' => 'Dokumen',
                                'xml' => 'XML',
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
                                $extension = e(strtoupper((string) ($record->file_extension ?? '-')));
                                $mimeType = e((string) ($record->file_mime_type ?? '-'));
                                $size = e((string) ($record->file_size_human ?? '-'));

                                if ($record->is_image_file) {
                                    return new HtmlString(<<<HTML
<div style="display:flex;align-items:flex-start;gap:14px;padding:12px;border:1px solid rgba(148,163,184,.25);border-radius:14px;background:rgba(15,23,42,.35);">
    <img src="{$url}" alt="{$alt}" style="width:140px;height:140px;object-fit:cover;border-radius:14px;border:1px solid rgba(148,163,184,.25);" />
    <div style="display:flex;flex-direction:column;gap:6px;min-width:0;">
        <span style="font-size:12px;color:#94a3b8;">File aktif saat ini</span>
        <span style="font-size:14px;color:#e2e8f0;font-weight:600;line-height:1.45;">{$alt}</span>
        <span style="font-size:12px;color:#94a3b8;word-break:break-all;">{$path}</span>
        <span style="font-size:12px;color:#94a3b8;">{$extension} &bull; {$size}</span>
        <a href="{$url}" target="_blank" style="font-size:12px;color:#60a5fa;text-decoration:underline;">Buka file</a>
    </div>
</div>
HTML);
                                }

                                return new HtmlString(<<<HTML
<div style="display:flex;align-items:flex-start;gap:14px;padding:12px;border:1px solid rgba(148,163,184,.25);border-radius:14px;background:rgba(15,23,42,.35);">
    <span style="display:inline-flex;align-items:center;justify-content:center;width:48px;height:48px;border-radius:12px;background:rgba(148,163,184,.14);">📄</span>
    <div style="display:flex;flex-direction:column;gap:6px;min-width:0;">
        <span style="font-size:12px;color:#94a3b8;">File aktif saat ini</span>
        <span style="font-size:14px;color:#e2e8f0;font-weight:600;line-height:1.45;">{$extension} file</span>
        <span style="font-size:12px;color:#94a3b8;">{$mimeType} &bull; {$size}</span>
        <span style="font-size:12px;color:#94a3b8;word-break:break-all;">{$path}</span>
        <a href="{$url}" target="_blank" style="font-size:12px;color:#60a5fa;text-decoration:underline;">Buka / download file</a>
    </div>
</div>
HTML);
                            })
                            ->columnSpanFull(),

                        SpatieMediaLibraryFileUpload::make('file')
                            ->label('Upload / Ganti File')
                            ->collection('file')
                            ->disk(config('uploads.disk', 'assets'))
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'image/gif',
                                'image/svg+xml',
                                'application/pdf',
                                'application/xml',
                                'text/xml',
                                'text/plain',
                                'application/zip',
                                'application/x-zip-compressed',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            ])
                            ->maxSize(10240)
                            ->visibility('public')
                            ->previewable(false)
                            ->openable(false)
                            ->downloadable(false)
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->required(fn ($record): bool => blank($record?->file_url))
                            ->helperText('Preview file aktif ditampilkan di bagian "Preview File Saat Ini". Gunakan field ini hanya untuk upload file baru atau mengganti file yang sudah ada. Mendukung gambar, PDF, XML, TXT, ZIP, DOCX, XLSX (maks 10MB).')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
