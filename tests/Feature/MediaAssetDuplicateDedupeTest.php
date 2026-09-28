<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\MediaAssetDedupeService;
use App\Services\MediaAssetFolderSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Entri ganda di File Manager: satu gambar tampil sebagai beberapa baris
 * karena isinya ducat di beberapa lokasi fisik dan setiap salinan ter-index
 * sebagai MediaAsset sendiri.
 *
 * Dua pertahanan yang diuji di sini:
 * 1. sync tidak lagi membuat baris untuk isi yang sudah terdaftar;
 * 2. dedupe hanya menghapus baris yang TIDAK direferensikan storefront.
 */
class MediaAssetDuplicateDedupeTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            File::delete($path);
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    private function writeFile(string $relativePath, string $contents): string
    {
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, $contents);
        $this->tempFiles[] = $absolutePath;

        return $absolutePath;
    }

    private function makeAsset(string $relativePath, ?string $name = null, string $folder = 'produk'): MediaAsset
    {
        return MediaAsset::query()->create([
            'name' => $name ?? pathinfo($relativePath, PATHINFO_FILENAME),
            'folder' => $folder,
            'path' => $relativePath,
        ]);
    }

    private function insertSpatieMedia(string $prefix, string $fileName, string $modelType): int
    {
        return DB::table('media')->insertGetId([
            'model_type' => $modelType,
            'model_id' => 1,
            'uuid' => (string) Str::uuid(),
            'collection_name' => 'product_logo',
            'name' => pathinfo($fileName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => 'image/webp',
            'disk' => 'assets',
            'conversions_disk' => 'assets',
            'size' => 20,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Kunci referensi legacy milik baris ini supaya isi file-nya tidak
     * dianggap "bebas" gara-gara baris kategori/produk dari test lain.
     */
    private function referenceKategori(string $subNama, string $relativePath): int
    {
        return DB::table('kategoris')->insertGetId([
            'nama' => Str::headline($subNama),
            'sub_nama' => $subNama,
            'status' => 'active',
            'tipe' => 'game',
            'thumbnail' => $relativePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ---------------------------------------------------------------- sync

    /**
     * Kebocoran lintas-test: baris kategori/produk dari test lain bisa
     * "melindungi" isi file kita lewat kebetulan, jadi isi unik dipakai
     * supaya isolasi tetap terjaga.
     */
    public function test_sync_does_not_index_a_second_copy_of_the_same_content(): void
    {
        $payload = 'content-payload-sync-' . Str::random(10);
        $original = '/assets/product_logo/dup-original.webp';
        $copy = '/assets/media/9001/dup-copy.webp';

        $this->writeFile($original, $payload);
        $this->writeFile($copy, $payload);

        $this->makeAsset($original);
        $this->referenceKategori('kategori-sync-dup', $original);

        $result = app(MediaAssetFolderSyncService::class)->sync();

        // Perilaku dulu: salinan isi identik TIDAK boleh jadi baris baru.
        // Kunci firstOrCreate(['path' => ...]) hanya unik per lokasi, jadi
        // tanpa penjaga ini setiap salinan menambah satu baris.
        $this->assertDatabaseMissing('media_assets', ['path' => $copy]);
        $this->assertDatabaseHas('media_assets', ['path' => $original]);

        // Repo kerja berisi pohon aset nyata, jadi jumlah globalnya tidak
        // stabil; cukup pastikan penjaganya melaporkan ada yang dilewati.
        $this->assertGreaterThanOrEqual(
            1,
            $result['skipped_duplicate_content'] ?? 0,
            'salinan isi identik harus dilewati, bukan di-index sebagai baris baru',
        );
    }

    /**
     * Fail-open: file dengan isi yang belum pernah terdaftar tetap di-index.
     * Penjaga dedupe tidak boleh menelan file baru.
     */
    public function test_sync_still_indexes_distinct_content(): void
    {
        $relativePath = '/assets/product_logo/dup-unique.webp';

        $this->writeFile($relativePath, 'content-payload-unique-' . Str::random(8));

        app(MediaAssetFolderSyncService::class)->sync();

        $this->assertDatabaseHas('media_assets', ['path' => $relativePath]);
    }

    // -------------------------------------------------------------- dedupe

    public function test_dedupe_dry_run_deletes_nothing(): void
    {
        $payload = 'content-payload-dry-' . Str::random(10);
        $protected = '/assets/product_logo/dedup-dry.webp';
        $orphan = '/assets/media/9002/dedup-dry.webp';

        $this->writeFile($protected, $payload);
        $this->writeFile($orphan, $payload);

        // Salinan tanpa referensi dibuat LEBIH DULU supaya id-nya lebih kecil:
        // dengan begitu test ini gagal kalau proteksi tidak jalan (aturan
        // "sisakan id terkecil" akan menyelamatkan baris yang salah).
        $orphanRow = $this->makeAsset($orphan);
        $keepRow = $this->makeAsset($protected);
        $this->referenceKategori('kategori-dry', $protected);

        $result = app(MediaAssetDedupeService::class)->dedupe(false);

        $this->assertSame(1, $result['deleted_rows']);
        $this->assertFalse($result['executed']);
        $this->assertDatabaseHas('media_assets', ['id' => $orphanRow->id]);
        $this->assertFileExists(public_path(ltrim($orphan, '/')));
        $this->assertDatabaseHas('media_assets', ['id' => $keepRow->id]);
    }

    /**
     * Aturan keselamatan utama: baris yang masih direferensikan storefront
     * TIDAK boleh dihapus, sementara salinan yang tidak dipakai dihapus.
     *
     * Baris tanpa referensi sengaja punya id LEBIH KECIL: kalau proteksi
     * mati, baris yang salah yang diselamatkan dan test ini gagal.
     */
    public function test_dedupe_deletes_only_unreferenced_duplicates(): void
    {
        $payload = 'content-payload-keep-' . Str::random(10);
        $referenced = '/assets/product_logo/dedup-keep.webp';
        $unreferenced = '/assets/media/9003/dedup-drop.webp';

        $this->writeFile($referenced, $payload);
        $this->writeFile($unreferenced, $payload);

        $dropRow = $this->makeAsset($unreferenced);
        $keepRow = $this->makeAsset($referenced);

        $this->referenceKategori('kategori-pakai', $referenced);

        $result = app(MediaAssetDedupeService::class)->dedupe(true);

        $this->assertSame(1, $result['deleted_rows']);
        $this->assertSame(1, $result['deleted_files']);

        // Baris + file yang direferensikan harus utuh.
        $this->assertDatabaseHas('media_assets', ['id' => $keepRow->id]);
        $this->assertFileExists(public_path(ltrim($referenced, '/')));

        // Salinan tanpa referensi hilang.
        $this->assertDatabaseMissing('media_assets', ['id' => $dropRow->id]);
        $this->assertFileDoesNotExist(public_path(ltrim($unreferenced, '/')));

        // Tidak ada kolom legacy yang dikosongkan: yang dihapus memang tidak dipakai.
        $this->assertSame(
            $referenced,
            DB::table('kategoris')->where('sub_nama', 'kategori-pakai')->value('thumbnail'),
        );
    }

    /**
     * Path Spatie tidak bisa dipindah (turun dari id row-nya), jadi baris yang
     * dipegang media milik model lain harus diperlakukan sebagai terlindungi —
     * kalau tidak, File Manager menampilkan gambar berbeda dari storefront.
     *
     * Baris tanpa referensi dibuat lebih dulu (id lebih kecil) sebagai kanari.
     */
    public function test_dedupe_protects_rows_referenced_by_spatie_media(): void
    {
        $prefix = trim((string) config('media-library.prefix', 'assets/media'), '/');
        $payload = 'content-payload-spatie-' . Str::random(10);

        // Path Spatie = <prefix>/<mediaId>/<file_name>, jadi id row-nya dulu
        // yang menentukan lokasi fisiknya.
        $mediaId = $this->insertSpatieMedia($prefix, 'dedup-spatie.webp', \App\Models\Kategori::class);
        $mediaRelative = '/' . $prefix . '/' . $mediaId . '/dedup-spatie.webp';
        $otherRelative = '/assets/product_logo/dedup-spatie.webp';

        $this->writeFile($mediaRelative, $payload);
        $this->writeFile($otherRelative, $payload);

        $otherRow = $this->makeAsset($otherRelative);
        $mediaRow = $this->makeAsset($mediaRelative);

        $result = app(MediaAssetDedupeService::class)->dedupe(true);

        // Salinan yang dipegang Spatie media dipertahankan; yang tidak
        // direferensikan siapa pun dihapus.
        $this->assertSame(1, $result['deleted_rows']);
        $this->assertDatabaseHas('media_assets', ['id' => $mediaRow->id]);
        $this->assertDatabaseMissing('media_assets', ['id' => $otherRow->id]);
        $this->assertFileExists(public_path(ltrim($mediaRelative, '/')));
        $this->assertFileDoesNotExist(public_path(ltrim($otherRelative, '/')));
    }

    /**
     * Kalau tidak ada satu pun anggota yang direferensikan, sisakan satu baris
     * supaya file-nya tidak menghilang dari File Manager.
     */
    public function test_dedupe_keeps_one_row_when_no_member_is_referenced(): void
    {
        $payload = 'content-payload-noref-' . Str::random(10);
        $first = '/assets/media/9004/no-ref.webp';
        $second = '/assets/media/9005/no-ref.webp';

        $this->writeFile($first, $payload);
        $this->writeFile($second, $payload);

        $firstRow = $this->makeAsset($first);
        $secondRow = $this->makeAsset($second);

        $result = app(MediaAssetDedupeService::class)->dedupe(true);

        $this->assertSame(1, $result['deleted_rows']);
        $this->assertDatabaseHas('media_assets', ['id' => $firstRow->id]);
        $this->assertDatabaseMissing('media_assets', ['id' => $secondRow->id]);
    }

    /**
     * Negatif: nama sama dengan isi berbeda BUKAN duplikat — dua produk boleh
     * memakai nama file yang sama untuk gambar yang berbeda.
     */
    public function test_dedupe_does_not_group_same_name_with_different_content(): void
    {
        $first = '/assets/media/9006/same-name.webp';
        $second = '/assets/media/9007/same-name.webp';

        $this->writeFile($first, 'content-payload-one-' . Str::random(10));
        $this->writeFile($second, 'content-payload-two-' . Str::random(10));

        $firstRow = $this->makeAsset($first);
        $secondRow = $this->makeAsset($second);

        $result = app(MediaAssetDedupeService::class)->dedupe(true);

        $this->assertSame(0, $result['deleted_rows']);
        $this->assertDatabaseHas('media_assets', ['id' => $firstRow->id]);
        $this->assertDatabaseHas('media_assets', ['id' => $secondRow->id]);
        $this->assertFileExists(public_path(ltrim($first, '/')));
        $this->assertFileExists(public_path(ltrim($second, '/')));
    }
}
