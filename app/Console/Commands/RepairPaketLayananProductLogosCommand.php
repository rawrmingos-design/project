<?php

namespace App\Console\Commands;

use App\Models\PaketLayanan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RepairPaketLayananProductLogosCommand extends Command
{
    protected $signature = 'product-logos:repair
        {--apply : Write recovered media paths to paket_layanans.product_logo}
        {--category= : Limit the report to a category ID or category code}
        {--limit= : Limit displayed rows}';

    protected $description = 'Repair paket_layanans.product_logo from PaketLayanan product_logo media records';

    public function handle(): int
    {
        $query = Media::query()
            ->where('collection_name', 'product_logo')
            ->where('model_type', PaketLayanan::class)
            ->orderBy('model_id');

        if ($category = $this->option('category')) {
            $query->whereIn('model_id', function ($subquery) use ($category): void {
                $subquery->from('paket_layanans')
                    ->select('paket_layanans.id')
                    ->join('layanans', 'layanans.id', '=', 'paket_layanans.layanan_id')
                    ->join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
                    ->where(function ($where) use ($category): void {
                        $where->where('kategoris.id', $category)
                            ->orWhere('kategoris.kode', $category);
                    });
            });
        }

        $mediaRows = $query->get(['id', 'model_id', 'file_name', 'disk']);
        $rows = [];
        $updated = 0;
        $skipped = 0;

        foreach ($mediaRows as $media) {
            $pivot = DB::table('paket_layanans')->where('id', $media->model_id)->first([
                'id',
                'product_logo',
            ]);

            if (! $pivot) {
                $skipped++;
                continue;
            }

            $path = $this->resolvePath($media);
            $current = trim((string) ($pivot->product_logo ?? ''));
            $status = $path === null
                ? 'missing_file'
                : ($current === $path ? 'already_synced' : ($current === '' ? 'recoverable' : 'different_existing'));

            if ($status === 'recoverable' && $this->option('apply')) {
                $updated += DB::table('paket_layanans')
                    ->where('id', $pivot->id)
                    ->where(function ($where): void {
                        $where->whereNull('product_logo')->orWhere('product_logo', '');
                    })
                    ->update([
                        'product_logo' => $path,
                        'updated_at' => now(),
                    ]);
                $status = 'repaired';
            }

            $rows[] = [
                'pivot_id' => $pivot->id,
                'media_id' => $media->id,
                'file' => $media->file_name,
                'path' => $path ?? '-',
                'current' => $current ?: '-',
                'status' => $status,
            ];
        }

        $displayRows = $this->option('limit')
            ? array_slice($rows, 0, max(1, (int) $this->option('limit')))
            : $rows;

        $this->table(['pivot_id', 'media_id', 'file', 'path', 'current', 'status'], $displayRows);
        $this->newLine();
        $this->line('Media records scanned: ' . count($mediaRows));
        $this->line('Rows displayed: ' . count($displayRows));
        $this->line('Repaired: ' . $updated);
        $this->line('Skipped invalid pivot: ' . $skipped);
        $this->line('Mode: ' . ($this->option('apply') ? 'APPLY' : 'DRY-RUN'));

        return self::SUCCESS;
    }

    private function resolvePath(Media $media): ?string
    {
        $relative = '/' . ltrim($media->getPathRelativeToRoot(), '/');

        if (($media->disk ?? 'assets') !== 'assets') {
            return $relative;
        }

        return is_file(public_path(ltrim($relative, '/'))) ? $relative : null;
    }
}
