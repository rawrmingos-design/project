<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Services\MediaAssetDeletionService;
use App\Services\MediaAssetFolderSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MediaAssetDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_deletes_managed_public_file_and_prevents_sync_from_recreating_asset(): void
    {
        $relativePath = '/assets/product_logo/test-delete-managed.png';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake image contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'test-delete-managed',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['asset_deleted']);
            $this->assertTrue($result['file_deleted']);
            $this->assertFalse(File::exists($absolutePath));
            $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);

            app(MediaAssetFolderSyncService::class)->sync();

            $this->assertDatabaseMissing('media_assets', ['path' => $relativePath]);
        } finally {
            File::delete($absolutePath);
        }
    }

    public function test_it_skips_physical_delete_outside_managed_directories(): void
    {
        $relativePath = '/unmanaged-file-manager-test.txt';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::put($absolutePath, 'do not delete');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'unmanaged-file-manager-test',
                'folder' => 'lainnya',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['asset_deleted']);
            $this->assertFalse($result['file_deleted']);
            $this->assertTrue($result['file_skipped']);
            $this->assertTrue(File::exists($absolutePath));
            $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        } finally {
            File::delete($absolutePath);
        }
    }

    public function test_it_deletes_optimized_variants_for_managed_images(): void
    {
        $relativePath = '/assets/product_logo/test-delete-variant.png';
        $absolutePath = public_path(ltrim($relativePath, '/'));
        $variantRelativePath = 'assets/optimized/product_logo/test-delete-variant-abc123-160.webp';
        $variantAbsolutePath = public_path($variantRelativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::ensureDirectoryExists(dirname($variantAbsolutePath));
        File::put($absolutePath, 'fake image contents');
        File::put($variantAbsolutePath, 'fake webp contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'test-delete-variant',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['file_deleted']);
            $this->assertContains($variantRelativePath, $result['variants_deleted']);
            $this->assertFalse(File::exists($absolutePath));
            $this->assertFalse(File::exists($variantAbsolutePath));
        } finally {
            File::delete($absolutePath);
            File::delete($variantAbsolutePath);
        }
    }

    /**
     * Regresi bug produksi: file unggahan Spatie Media Library berada di
     * prefix `assets/media/<mediaId>/<file>` (config media-library.prefix).
     * Direktori itu TIDAK ada di managedDirectories() sehingga
     * isDeletablePublicFile() mengembalikan false: file fisik tetap
     * tertinggal di disk walau record sudah dihapus dari File Manager.
     * Gejala ke pengguna: "gambar sudah dihapus tapi masih tampil".
     */
    public function test_it_deletes_spatie_media_prefix_files(): void
    {
        $prefix = trim((string) config('media-library.prefix', 'assets/media'), '/');
        $this->assertNotSame('', $prefix, 'prefix media library harus terkonfigurasi');

        $relativePath = '/' . $prefix . '/99901/01M1684CKWS8BNTX65RYXZ0SPRTEST.webp';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake spatie image contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => '01M1684CKWS8BNTX65RYXZ0SPRTEST',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['asset_deleted']);
            $this->assertTrue(
                $result['file_deleted'],
                'file di prefix spatie (assets/media) harus ikut terhapus, bukan di-skip',
            );
            $this->assertFalse($result['file_skipped']);
            $this->assertFalse(File::exists($absolutePath));
            $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        } finally {
            File::delete($absolutePath);
        }
    }

    /**
     * Folder sync tidak boleh membuat ulang asset setelah file spatie
     * prefix dihapus — kalau tidak, gambar "muncul kembali" di File Manager.
     */
    public function test_folder_sync_does_not_recreate_deleted_spatie_prefix_asset(): void
    {
        $prefix = trim((string) config('media-library.prefix', 'assets/media'), '/');
        $relativePath = '/' . $prefix . '/99902/resync-guard-test.webp';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'resync-guard-test',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertFalse(File::exists($absolutePath));

            app(MediaAssetFolderSyncService::class)->sync();

            $this->assertDatabaseMissing('media_assets', ['path' => $relativePath]);
        } finally {
            File::delete($absolutePath);
        }
    }

    /**
     * Varian optimized dari file spatie prefix juga harus ikut dibersihkan.
     */
    public function test_it_deletes_optimized_variants_for_spatie_prefix_images(): void
    {
        $prefix = trim((string) config('media-library.prefix', 'assets/media'), '/');
        $relativePath = '/' . $prefix . '/99903/variant-spatie-test.webp';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake image contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'variant-spatie-test',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['file_deleted']);
            $this->assertFalse(File::exists($absolutePath));
        } finally {
            File::delete($absolutePath);
        }
    }

    /**
     * File yang sama bisa terdaftar di DUA tempat: sebagai MediaAsset (File
     * Manager) DAN sebagai media milik PaketLayanan (unggahan form produk).
     * Menghapus dari File Manager harus membersihkan keduanya — kalau row
     * milik PaketLayanan dibiarkan, form produk masih melihat media "ada"
     * dan menuliskannya kembali ke kolom legacy saat disimpan, sehingga
     * gambar yang sudah dihapus muncul lagi di frontend.
     */
    public function test_it_deletes_sibling_spatie_media_pointing_to_same_file(): void
    {
        $prefix = trim((string) config('media-library.prefix', 'assets/media'), '/');

        // Path Spatie = <prefix>/<mediaId>/<file_name>, jadi id row-nya
        // dulu yang menentukan path fisik.
        $siblingId = DB::table('media')->insertGetId([
            'model_type' => \App\Models\PaketLayanan::class,
            'model_id' => 1,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'collection_name' => 'product_logo',
            'name' => 'sibling-shared-file',
            'file_name' => 'sibling-shared-file.webp',
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

        $relativePath = '/' . $prefix . '/' . $siblingId . '/sibling-shared-file.webp';
        $absolutePath = public_path(ltrim($relativePath, '/'));

        try {
            File::ensureDirectoryExists(dirname($absolutePath));
            File::put($absolutePath, 'fake shared contents');

            // Row milik MediaAsset sendiri (dibuat manual, tanpa file media).
            $asset = MediaAsset::query()->create([
                'name' => 'sibling-shared-file',
                'folder' => 'produk',
                'path' => $relativePath,
            ]);

            $this->assertFileExists($absolutePath);
            $this->assertSame(
                $absolutePath,
                MediaAsset::find($asset->id)->resolveAbsolutePath(),
            );

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            $this->assertTrue($result['asset_deleted']);
            $this->assertFalse(File::exists($absolutePath));
            $this->assertDatabaseMissing('media', ['id' => $siblingId]);
        } finally {
            File::delete($absolutePath);
        }
    }

    /**
     * Scoping harus ketat: file dengan NAMA SAMA di direktori BEDA tidak
     * boleh ikut terhapus. Prod punya 8 nama file yang dipakai 9–16 row
     * masing-masing; penghapusan berbasis nama file akan merusak produk lain.
     */
    public function test_it_does_not_delete_media_with_same_filename_in_other_directory(): void
    {
        $prefix = trim((string) config('media-library.prefix', 'assets/media'), '/');

        // Row asing dibuat dulu: path Spatie bergantung pada id row-nya.
        $otherId = DB::table('media')->insertGetId([
            'model_type' => \App\Models\PaketLayanan::class,
            'model_id' => 2,
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'collection_name' => 'product_logo',
            'name' => 'shared-name',
            'file_name' => 'shared-name.webp',
            'mime_type' => 'image/webp',
            'disk' => 'assets',
            'conversions_disk' => 'assets',
            'size' => 15,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // File lain: NAMA SAMA, direktori berbeda.
        $otherRelative = '/' . $prefix . '/' . $otherId . '/shared-name.webp';
        $otherAbsolute = public_path(ltrim($otherRelative, '/'));

        // Target: direktori sendiri, nama file juga sama.
        $targetRelative = '/' . $prefix . '/99905/shared-name.webp';
        $targetAbsolute = public_path(ltrim($targetRelative, '/'));

        try {
            File::ensureDirectoryExists(dirname($otherAbsolute));
            File::put($otherAbsolute, 'other contents');

            File::ensureDirectoryExists(dirname($targetAbsolute));
            File::put($targetAbsolute, 'target contents');

            $asset = MediaAsset::query()->create([
                'name' => 'shared-name',
                'folder' => 'produk',
                'path' => $targetRelative,
            ]);

            app(MediaAssetDeletionService::class)->delete($asset);

            // File & row di direktori lain harus UTUH.
            $this->assertTrue(File::exists($otherAbsolute), 'file di direktori lain tidak boleh terhapus');
            $this->assertDatabaseHas('media', ['id' => $otherId]);
        } finally {
            File::delete($targetAbsolute);
            File::delete($otherAbsolute);
            DB::table('media')->where('model_id', 2)->where('collection_name', 'product_logo')->delete();
        }
    }

    public function test_it_clears_legacy_references_before_deleting_asset(): void
    {
        $relativePath = 'assets/product_logo/test-clear-ref.png';
        $absolutePath = public_path($relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'fake image contents');

        try {
            $asset = MediaAsset::query()->create([
                'name' => 'test-clear-ref',
                'folder' => 'produk',
                'path' => '/' . $relativePath,
            ]);

            // Satu kategori memakai asset ini di dua kolom dengan format
            // path berbeda (dengan/tanpa leading slash).
            $kategoriId = DB::table('kategoris')->insertGetId([
                'nama' => 'Kategori Terpakai',
                'sub_nama' => 'kategori-terpakai',
                'status' => 'active',
                'tipe' => 'game',
                'thumbnail' => '/' . $relativePath,
                'banner' => $relativePath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Produk lain memakai asset yang sama sebagai logo.
            $layananId = DB::table('layanans')->insertGetId([
                'kategori_id' => (string) $kategoriId,
                'layanan' => 'Produk Terpakai',
                'provider_id' => 'PRV-1',
                'harga' => 10000,
                'harga_member' => 10000,
                'harga_platinum' => 10000,
                'harga_gold' => 10000,
                'profit_member' => 0,
                'profit_platinum' => 0,
                'profit_gold' => 0,
                'status' => 'active',
                'provider' => 'manual',
                'product_logo' => $relativePath,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Row lain yang TIDAK memakai asset — tidak boleh tersentuh.
            $kategoriLainId = DB::table('kategoris')->insertGetId([
                'nama' => 'Kategori Lain',
                'sub_nama' => 'kategori-lain',
                'status' => 'active',
                'tipe' => 'game',
                'thumbnail' => 'assets/product_logo/lain.png',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Berita memakai asset sebagai banner — skema beritas TIDAK
            // punya kolom judul (egymarket), label harus fallback ke tipe
            // dan query tidak boleh 42S22 Unknown column.
            $beritaId = DB::table('beritas')->insertGetId([
                'path' => '/' . $relativePath,
                'tipe' => 'banner_game',
                'deskripsi' => 'Banner promo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Metode pembayaran memakai asset juga — kolom images NOT NULL
            // (skema legacy), harus dikosongkan ke '' bukan NULL.
            $metodeId = DB::table('methods')->insertGetId([
                'name' => 'QRIS Test',
                'images' => '/' . $relativePath,
                'code' => 'QRIS-TEST',
                'keterangan' => 'Metode test',
                'tipe' => 'qris',
                'payment' => 'qris',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $result = app(MediaAssetDeletionService::class)->delete($asset);

            // Referensi ditemukan dan dikosongkan sebelum file dihapus.
            $this->assertTrue($result['asset_deleted']);
            $this->assertTrue($result['file_deleted']);
            $this->assertSame(5, $result['references_cleared']);

            $foundTables = collect($result['references_found'])->pluck('table')->all();
            $this->assertContains('kategoris', $foundTables);
            $this->assertContains('layanans', $foundTables);
            $this->assertContains('beritas', $foundTables);

            // Label berita fallback ke tipe karena kolom judul tidak ada.
            $beritaLabel = collect($result['references_found'])
                ->firstWhere('table', 'beritas')['label'] ?? '';
            $this->assertStringContainsString('banner_game', $beritaLabel);

            $this->assertNull(DB::table('kategoris')->where('id', $kategoriId)->value('thumbnail'));
            $this->assertNull(DB::table('kategoris')->where('id', $kategoriId)->value('banner'));
            $this->assertNull(DB::table('layanans')->where('id', $layananId)->value('product_logo'));
            $this->assertNull(DB::table('beritas')->where('id', $beritaId)->value('path'));

            // Kolom NOT NULL dikosongkan ke '' (bukan NULL) supaya lolos
            // constraint skema legacy egymarket/istanatopup.
            $this->assertSame('', DB::table('methods')->where('id', $metodeId)->value('images'));
            $this->assertSame(5, $result['references_cleared']);

            // Row lain tidak boleh ikut dikosongkan.
            $this->assertSame(
                'assets/product_logo/lain.png',
                DB::table('kategoris')->where('id', $kategoriLainId)->value('thumbnail'),
            );

            // Asset dan file fisik benar-benar hilang.
            $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
            $this->assertFalse(File::exists($absolutePath));
        } finally {
            File::delete($absolutePath);
        }
    }
}
