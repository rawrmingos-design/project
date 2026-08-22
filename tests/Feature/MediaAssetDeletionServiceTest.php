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
            $this->assertSame(4, $result['references_cleared']);

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
