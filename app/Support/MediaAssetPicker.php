<?php

namespace App\Support;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\HasMedia;

class MediaAssetPicker
{
    protected const FOLDER_LABELS = [
        'produk' => 'Produk',
        'kategori' => 'Kategori',
        'banner' => 'Banner',
        'artikel' => 'Artikel',
        'logo' => 'Logo',
        'lainnya' => 'Lainnya',
    ];

    public static function isUsable($value): bool
    {
        if (blank($value)) {
            return false;
        }

        $asset = MediaAsset::find($value);

        return (bool) ($asset?->file_url);
    }

    public static function getFolderOptions(?array $folders = null): array
    {
        $folderLabels = static::FOLDER_LABELS;

        if (blank($folders)) {
            return $folderLabels;
        }

        return collect($folders)
            ->filter(fn ($folder): bool => array_key_exists($folder, $folderLabels))
            ->mapWithKeys(fn ($folder): array => [$folder => $folderLabels[$folder]])
            ->all();
    }

    public static function resolveFolders(?string $folder = null, ?array $allowedFolders = null): ?array
    {
        $allowedFolders = blank($allowedFolders) ? array_keys(static::FOLDER_LABELS) : array_values($allowedFolders);

        if (filled($folder) && in_array($folder, $allowedFolders, true)) {
            return [$folder];
        }

        return $allowedFolders;
    }

    public static function getSearchResults(string $search, ?array $folders = null): array
    {
        return static::getSearchResultsForDisplay($search, $folders, false);
    }

    public static function getSearchResultsForDisplay(string $search, ?array $folders = null, bool $allowHtml = false): array
    {
        $query = MediaAsset::query();

        if (! empty($folders)) {
            $query->whereIn('folder', $folders);
        }

        $search = trim($search);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('folder', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->filter(fn (MediaAsset $asset): bool => filled($asset->file_url))
            ->take(50)
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => $allowHtml ? static::formatHtmlLabel($asset) : static::formatLabel($asset)])
            ->all();
    }

    public static function getGalleryAssets(?string $search = null, ?array $folders = null, int $limit = 24): array
    {
        $query = MediaAsset::query();

        if (! empty($folders)) {
            $query->whereIn('folder', $folders);
        }

        $search = trim((string) $search);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('folder', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('updated_at')
            ->limit($limit * 3)
            ->get()
            ->filter(fn (MediaAsset $asset): bool => filled($asset->file_url))
            ->take($limit)
            ->map(fn (MediaAsset $asset): array => [
                'id' => (string) $asset->getKey(),
                'name' => $asset->name,
                'label' => static::formatLabel($asset),
                'url' => $asset->file_url,
                'alt' => $asset->alt_text ?: $asset->name,
                'folder' => $asset->folder ? ucfirst($asset->folder) : 'Lainnya',
                'path' => $asset->resolveRelativePath() ?: '-',
            ])
            ->values()
            ->all();
    }

    public static function getUsableAssetIds(?array $folders = null): array
    {
        $query = MediaAsset::query();

        if (! empty($folders)) {
            $query->whereIn('folder', $folders);
        }

        return $query
            ->get()
            ->filter(fn (MediaAsset $asset): bool => filled($asset->file_url))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public static function getOptionLabel($value): ?string
    {
        return static::getOptionLabelForDisplay($value, false);
    }

    public static function getOptionLabelForDisplay($value, bool $allowHtml = false): ?string
    {
        if (blank($value)) {
            return null;
        }

        $asset = MediaAsset::find($value);

        if (! $asset || blank($asset->file_url)) {
            return null;
        }

        return $allowHtml ? static::formatHtmlLabel($asset) : static::formatLabel($asset);
    }

    public static function formatLabel(MediaAsset $asset): string
    {
        $folder = $asset->folder ? '[' . ucfirst($asset->folder) . '] ' : '';

        return $folder . $asset->name;
    }

    public static function formatHtmlLabel(MediaAsset $asset): string
    {
        $label = e(static::formatLabel($asset));
        $url = e($asset->file_url);
        $alt = e($asset->alt_text ?: $asset->name);
        $path = e($asset->resolveRelativePath() ?: '-');

        return <<<HTML
<div style="display:flex;align-items:center;gap:12px;padding:4px 2px;">
    <img src="{$url}" alt="{$alt}" style="width:48px;height:48px;object-fit:cover;border-radius:10px;border:1px solid rgba(148,163,184,.2);" />
    <div style="display:flex;flex-direction:column;gap:2px;min-width:0;">
        <span style="font-size:13px;font-weight:600;color:#e2e8f0;line-height:1.35;">{$label}</span>
        <span style="font-size:11px;color:#94a3b8;line-height:1.35;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:28rem;">{$path}</span>
    </div>
</div>
HTML;
    }

    public static function makeModalAction(
        string $name,
        string $statePath,
        string $label,
        array $folders,
        ?string $defaultFolder = null,
    ): Action {
        $defaultFolder = in_array($defaultFolder, $folders, true) ? $defaultFolder : null;

        return Action::make($name)
            ->label($label)
            ->icon('heroicon-o-photo')
            ->color('primary')
            ->link()
            ->slideOver()
            ->modalWidth(Width::FiveExtraLarge)
            ->modalHeading($label)
            ->modalDescription('Pilih asset yang sudah ada di Media Library. Kamu tetap bisa pindah ke mode upload baru kapan saja.')
            ->modalSubmitActionLabel('Gunakan Asset Ini')
            ->modalCancelActionLabel('Tutup')
            ->fillForm(function ($get) use ($statePath, $defaultFolder, $folders): array {
                $selectedAssetId = $get($statePath);
                $selectedAsset = filled($selectedAssetId) ? MediaAsset::find($selectedAssetId) : null;
                $resolvedFolder = $selectedAsset?->folder;

                if (! in_array($resolvedFolder, $folders, true)) {
                    $resolvedFolder = $defaultFolder;
                }

                return [
                    'folder_filter' => $resolvedFolder,
                    'selected_asset_id' => static::isUsable($selectedAssetId) ? $selectedAssetId : null,
                ];
            })
            ->schema([
                Select::make('folder_filter')
                    ->label('Filter Folder')
                    ->options(static::getFolderOptions($folders))
                    ->default($defaultFolder)
                    ->native(false)
                    ->live()
                    ->columnSpanFull(),
                TextInput::make('asset_search')
                    ->label('Cari Asset')
                    ->placeholder('Cari nama, alt text, folder, atau path...')
                    ->dehydrated(false)
                    ->live()
                    ->columnSpanFull(),
                ViewField::make('selected_asset_id')
                    ->label('Galeri Asset')
                    ->required()
                    ->view('filament.forms.components.media-asset-gallery-picker')
                    ->viewData(fn (Get $get): array => [
                        'assets' => static::getGalleryAssets(
                            $get('asset_search'),
                            static::resolveFolders($get('folder_filter'), $folders),
                        ),
                    ])
                    ->columnSpanFull(),
                Placeholder::make('selected_asset_preview')
                    ->label('Preview Asset Terpilih')
                    ->content(fn ($get) => static::renderPreview($get('selected_asset_id')))
                    ->columnSpanFull(),
                Placeholder::make('asset_gallery_hint')
                    ->label('Catatan')
                    ->content(fn (): HtmlString => new HtmlString('<span class="text-sm text-gray-400">Klik langsung thumbnail di galeri untuk memilih asset. Gunakan pencarian atau filter folder kalau daftar masih terlalu ramai.</span>'))
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, $set) use ($statePath): void {
                $set($statePath, filled($data['selected_asset_id'] ?? null) ? (int) $data['selected_asset_id'] : null);
            });
    }

    public static function makeClearAction(string $name, string $statePath): Action
    {
        return Action::make($name)
            ->label('Clear')
            ->icon('heroicon-o-x-mark')
            ->color('gray')
            ->link()
            ->visible(fn ($get): bool => filled($get($statePath)))
            ->action(fn ($set): mixed => $set($statePath, null));
    }

    public static function renderPreview($value): HtmlString
    {
        return static::renderSelectedOrCurrentPreview($value);
    }

    public static function renderSelectedOrCurrentPreview(
        $value,
        ?Model $record = null,
        ?string $collection = null,
        ?string $legacyColumn = null,
    ): HtmlString {
        if (filled($value)) {
            $asset = MediaAsset::find($value);

            if (! $asset) {
                return new HtmlString('<span class="text-sm text-danger-500">Asset tidak ditemukan.</span>');
            }

            $url = $asset->file_url;

            if (! $url) {
                return new HtmlString('<span class="text-sm text-warning-500">Asset belum punya file.</span>');
            }

            return static::buildPreviewCard(
                eyebrow: 'Asset akan digunakan',
                label: static::formatLabel($asset),
                url: $url,
                alt: $asset->alt_text ?: $asset->name,
                source: 'Media Library',
                path: $asset->resolveRelativePath(),
            );
        }

        if ($current = static::resolveCurrentPreviewData($record, $collection, $legacyColumn)) {
            return static::buildPreviewCard(
                eyebrow: 'Gambar aktif saat ini',
                label: $current['label'],
                url: $current['url'],
                alt: $current['alt'],
                source: $current['source'],
                path: $current['path'],
                note: 'Belum ada asset baru dipilih. Jika disimpan tanpa perubahan gambar, gambar lama tetap dipakai.',
            );
        }

        return new HtmlString('<span class="text-sm text-gray-400">Belum ada gambar aktif atau asset terpilih.</span>');
    }

    public static function renderCurrentPreview(
        ?Model $record,
        ?string $collection = null,
        ?string $legacyColumn = null,
    ): HtmlString {
        if (! $record) {
            return new HtmlString('<span class="text-sm text-gray-400">Preview akan muncul setelah record disimpan.</span>');
        }

        $current = static::resolveCurrentPreviewData($record, $collection, $legacyColumn);

        if (! $current) {
            return new HtmlString('<span class="text-sm text-gray-400">Belum ada gambar aktif pada record ini.</span>');
        }

        return static::buildPreviewCard(
            eyebrow: 'Gambar aktif saat ini',
            label: $current['label'],
            url: $current['url'],
            alt: $current['alt'],
            source: $current['source'],
            path: $current['path'],
        );
    }

    public static function resolveCurrentMediaAssetId(
        ?Model $record,
        ?string $collection = null,
        ?string $legacyColumn = null,
    ): ?int {
        $current = static::resolveCurrentPreviewData($record, $collection, $legacyColumn);

        return $current['asset_id'] ?? null;
    }

    protected static function resolveCurrentPreviewData(
        ?Model $record,
        ?string $collection = null,
        ?string $legacyColumn = null,
    ): ?array {
        if (! $record) {
            return null;
        }

        if (method_exists($record, 'resolveActiveMediaPreviewData')) {
            $resolved = $record->resolveActiveMediaPreviewData($collection, $legacyColumn);

            if (is_array($resolved) && filled($resolved['url'] ?? null)) {
                return [
                    'label' => $resolved['label'] ?? basename((string) ($resolved['path'] ?? $resolved['url'] ?? 'gambar')),
                    'url' => $resolved['url'],
                    'alt' => $resolved['alt'] ?? ($resolved['label'] ?? 'Preview image'),
                    'source' => $resolved['source'] ?? 'Record',
                    'path' => $resolved['path'] ?? null,
                    'asset_id' => $resolved['asset_id'] ?? static::findAssetIdByPath($resolved['path'] ?? null),
                ];
            }
        }

        if ($record instanceof HasMedia && filled($collection)) {
            $media = $record->getFirstMedia($collection);

            if ($media && is_file($media->getPath())) {
                $relativePath = '/' . ltrim($media->getPathRelativeToRoot(), '/');

                return [
                    'label' => $media->name ?: pathinfo($media->file_name, PATHINFO_FILENAME),
                    'url' => asset(ltrim($relativePath, '/')),
                    'alt' => $media->name ?: pathinfo($media->file_name, PATHINFO_FILENAME),
                    'source' => 'Upload Record',
                    'path' => $relativePath,
                    'asset_id' => static::findAssetIdByPath($relativePath),
                ];
            }
        }

        $legacyColumn ??= method_exists($record, 'getLegacyMediaColumnMap')
            ? ($record->getLegacyMediaColumnMap()[$collection] ?? null)
            : null;

        $legacyPath = $legacyColumn ? data_get($record, $legacyColumn) : null;

        if (! filled($legacyPath)) {
            return null;
        }

        $normalizedPath = static::normalizeRelativePath($legacyPath);
        $assetId = static::findAssetIdByPath($normalizedPath);
        $asset = $assetId ? MediaAsset::find($assetId) : null;

        return [
            'label' => $asset?->name ?: pathinfo((string) $normalizedPath, PATHINFO_FILENAME),
            'url' => static::toPublicUrl($normalizedPath),
            'alt' => $asset?->alt_text ?: ($asset?->name ?: pathinfo((string) $normalizedPath, PATHINFO_FILENAME)),
            'source' => $asset ? 'Media Library' : 'Path Legacy',
            'path' => $normalizedPath,
            'asset_id' => $assetId,
        ];
    }

    protected static function findAssetIdByPath(?string $path): ?int
    {
        $normalizedPath = static::normalizeRelativePath($path);

        if (! $normalizedPath) {
            return null;
        }

        $candidates = array_values(array_unique([
            $normalizedPath,
            ltrim($normalizedPath, '/'),
        ]));

        return MediaAsset::query()
            ->whereIn('path', $candidates)
            ->value('id');
    }

    protected static function normalizeRelativePath(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return '/' . ltrim($path, '/');
    }

    protected static function toPublicUrl(?string $path): ?string
    {
        $path = static::normalizeRelativePath($path);

        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }

    protected static function buildPreviewCard(
        string $eyebrow,
        string $label,
        string $url,
        string $alt,
        ?string $source = null,
        ?string $path = null,
        ?string $note = null,
    ): HtmlString {
        $label = e($label);
        $url = e($url);
        $alt = e($alt);
        $source = $source ? e($source) : null;
        $path = $path ? e($path) : null;
        $note = $note ? e($note) : null;

        $meta = collect([$source, $path])->filter()->implode(' | ');
        $metaHtml = $meta !== '' ? '<span style="font-size:12px;color:#94a3b8;">' . e($meta) . '</span>' : '';
        $noteHtml = $note ? '<span style="font-size:12px;color:#cbd5e1;line-height:1.45;">' . $note . '</span>' : '';

        return new HtmlString(<<<HTML
<div style="display:flex;align-items:flex-start;gap:12px;padding:10px 12px;border:1px solid rgba(148,163,184,.25);border-radius:12px;background:rgba(15,23,42,.35);">
    <img src="{$url}" alt="{$alt}" style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid rgba(148,163,184,.2);" />
    <div style="display:flex;flex-direction:column;gap:4px;min-width:0;">
        <span style="font-size:12px;color:#94a3b8;">{$eyebrow}</span>
        <span style="font-size:14px;color:#e2e8f0;font-weight:600;line-height:1.45;">{$label}</span>
        {$metaHtml}
        {$noteHtml}
    </div>
</div>
HTML);
    }
}
