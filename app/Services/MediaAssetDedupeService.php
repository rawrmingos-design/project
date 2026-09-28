<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Menghapus baris media_assets yang isinya identik tapi tidak dipakai.
 *
 * Aturan keselamatan (jangan dilonggarkan tanpa bukti baru):
 *
 * 1. Grup dibentuk dari md5 ISI file, bukan path/nama. Path unik secara
 *    skema, jadi mendeteksi lewat path mustahil; `file_name` hanya menangkap
 *    pasangan bernama sama dan melewatkan pasangan ULID.
 * 2. Sebuah baris DILINDUNGI kalau path-nya masih direferensikan kolom legacy
 *    (kategoris.thumbnail, paket_layanans.product_logo, ...). Baris seperti itu
 *    yang dirender storefront.
 * 3. Sebuah baris juga DILINDUNGI kalau ada baris Spatie `media` yang menunjuk
 *    path itu. Path media Spatie diturunkan dari id row-nya dan TIDAK BISA
 *    dipindahkan, jadi memindahkan referensi legacy ke "satu canonical" akan
 *    membuat storefront menampilkan gambar berbeda dari yang tampil di File
 *    Manager milik model itu. Karena itu service ini TIDAK pernah repoint.
 * 4. Hanya baris yang TIDAK dilindungi yang dihapus. Grup yang seluruh
 *    anggotanya dilindungi dibiarkan utuh (tidak dipaksa jadi satu).
 * 5. Default DRY-RUN. Penulisan hanya dengan $execute = true.
 */
class MediaAssetDedupeService
{
    public function dedupe(bool $execute = false, ?callable $logger = null): array
    {
        $protected = $this->protectedPaths();

        $groups = 0;
        $dedupeableGroups = 0;
        $keptProtected = 0;
        $groupsWithoutReference = 0;
        $deletedRows = 0;
        $deletedFiles = 0;
        $failedFiles = 0;
        $freedBytes = 0;

        foreach ($this->contentGroups() as $signature => $members) {
            if (count($members) < 2) {
                continue;
            }

            $groups++;

            $keep = [];
            $unreferenced = [];

            foreach ($members as $member) {
                if (isset($protected[$this->normalize($member->path)])) {
                    $keep[] = $member;
                } else {
                    $unreferenced[] = $member;
                }
            }

            $hasNoReference = count($keep) === 0;
            $keptProtected += count($keep);

            if ($hasNoReference) {
                $groupsWithoutReference++;

                // Tidak ada anggota yang direferensikan: sisakan satu supaya
                // file-nya tetap terlihat di File Manager dan tidak menghilang
                // diam-diam dari mata admin.
                if (count($unreferenced) > 1) {
                    usort($unreferenced, static fn ($a, $b): int => $a->id <=> $b->id);
                    $keep[] = array_shift($unreferenced);
                }
            }

            $drop = $unreferenced;

            if (count($drop) === 0) {
                continue;
            }

            $dedupeableGroups++;

            foreach ($drop as $member) {
                $absolutePath = public_path(ltrim((string) $member->path, '/'));
                $size = is_file($absolutePath) ? (int) filesize($absolutePath) : 0;

                if ($logger) {
                    $logger(sprintf(
                        '[%s] Hapus #%d %s (isi identik dengan %d baris lain%s)',
                        $execute ? 'EXECUTE' : 'DRY-RUN',
                        $member->id,
                        $member->path,
                        count($members),
                        $hasNoReference ? ', tidak ada yang direferensikan' : ''
                    ));
                }

                $deletedRows++;

                if (! $execute) {
                    $freedBytes += $size;
                    continue;
                }

                $asset = MediaAsset::query()->find($member->id);

                if (! $asset) {
                    continue;
                }

                $result = app(MediaAssetDeletionService::class)->delete($asset);

                if ($result['file_deleted']) {
                    $deletedFiles++;
                    $freedBytes += $size;
                } elseif ($result['file_skipped']) {
                    $failedFiles++;
                }
            }
        }

        return [
            'groups' => $groups,
            'dedupeable_groups' => $dedupeableGroups,
            'kept_protected_rows' => $keptProtected,
            'groups_without_reference' => $groupsWithoutReference,
            'deleted_rows' => $deletedRows,
            'deleted_files' => $deletedFiles,
            'failed_files' => $failedFiles,
            'freed_bytes' => $freedBytes,
            'executed' => $execute,
        ];
    }

    /**
     * Path yang tidak boleh dihapus: direferensikan kolom legacy, atau
     * dipegang baris Spatie media.
     *
     * @return array<string, string> normalized path => alasan
     */
    public function protectedPaths(): array
    {
        $protected = [];
        $schema = DB::getSchemaBuilder();

        foreach ($this->legacyReferences() as $target) {
            if (! $schema->hasTable($target['table']) || ! $schema->hasColumn($target['table'], $target['column'])) {
                continue;
            }

            foreach (DB::table($target['table'])->whereNotNull($target['column'])->pluck($target['column']) as $value) {
                $normalized = $this->normalize($value);

                if ($normalized !== '') {
                    $protected[$normalized] = $target['table'] . '.' . $target['column'];
                }
            }
        }

        if ($schema->hasTable('media')) {
            foreach (Media::query()->where('disk', 'assets')->get() as $media) {
                $normalized = $this->normalize($media->getPathRelativeToRoot());

                if ($normalized !== '') {
                    $protected[$normalized] = 'media#' . $media->id;
                }
            }
        }

        return $protected;
    }

    /**
     * Kelompokkan semua baris media_assets berisi file yang ada menurut md5
     * isinya. Baris tanpa file fisik tidak pernah ikut (tidak ada yang bisa
     * dibandingkan dan dihapus).
     *
     * @return array<string, array<int, object>>
     */
    private function contentGroups(): array
    {
        $groups = [];

        MediaAsset::query()
            ->select('id', 'path')
            ->orderBy('id')
            ->chunkById(500, function ($assets) use (&$groups): void {
                foreach ($assets as $asset) {
                    $relative = ltrim((string) $asset->path, '/');

                    if ($relative === '') {
                        continue;
                    }

                    $absolutePath = public_path($relative);

                    if (! is_file($absolutePath) || ! is_readable($absolutePath)) {
                        continue;
                    }

                    $signature = @md5_file($absolutePath);

                    if ($signature === false) {
                        continue;
                    }

                    $groups[$signature][] = $asset;
                }
            });

        return $groups;
    }

    /**
     * @return array<int, array{table:string,column:string}>
     */
    private function legacyReferences(): array
    {
        return [
            ['table' => 'kategoris', 'column' => 'thumbnail'],
            ['table' => 'kategoris', 'column' => 'banner'],
            ['table' => 'layanans', 'column' => 'product_logo'],
            ['table' => 'paket_layanans', 'column' => 'product_logo'],
            ['table' => 'artikels', 'column' => 'thumbnail'],
            ['table' => 'beritas', 'column' => 'path'],
            ['table' => 'methods', 'column' => 'images'],
            ['table' => 'setting_webs', 'column' => 'logo_favicon'],
            ['table' => 'setting_webs', 'column' => 'logo_header'],
            ['table' => 'setting_webs', 'column' => 'logo_footer'],
        ];
    }

    /**
     * Samakan bentuk nilai legacy (sebagian pakai leading slash, sebagian
     * tidak) supaya perbandingan tidak menghasilkan negatif palsu.
     */
    private function normalize(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        return ltrim(str_replace('\\', '/', $path), '/');
    }
}
