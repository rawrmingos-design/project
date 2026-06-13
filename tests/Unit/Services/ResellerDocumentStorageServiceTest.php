<?php

namespace Tests\Unit\Services;

use App\Services\ResellerDocumentStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ResellerDocumentStorageServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResellerDocumentStorageService $service;
    private int $testUserId = 987654321;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ResellerDocumentStorageService();
        $this->cleanupTestDirectory();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestDirectory();

        parent::tearDown();
    }

    /**
     * Test buildRelativePath generates path under public reseller documents directory.
     */
    public function test_build_relative_path_uses_expected_directory_structure(): void
    {
        $path = $this->service->buildRelativePath($this->testUserId, 'identity', 'jpg');

        $this->assertStringStartsWith("assets/reseller-documents/{$this->testUserId}/identity_", $path);
        $this->assertStringEndsWith('.jpg', $path);
    }

    /**
     * Test buildRelativePath normalizes document type and extension.
     */
    public function test_build_relative_path_normalizes_document_type_and_extension(): void
    {
        $path = $this->service->buildRelativePath($this->testUserId, 'Business Proof', '.PNG');

        $this->assertStringStartsWith("assets/reseller-documents/{$this->testUserId}/business_proof_", $path);
        $this->assertStringEndsWith('.png', $path);
    }

    /**
     * Test buildRelativePath falls back to bin extension when extension is blank.
     */
    public function test_build_relative_path_uses_bin_when_extension_is_blank(): void
    {
        $path = $this->service->buildRelativePath($this->testUserId, 'identity', '');

        $this->assertStringEndsWith('.bin', $path);
    }

    /**
     * Test store moves uploaded file and returns relative path.
     */
    public function test_store_moves_uploaded_file_and_returns_relative_path(): void
    {
        $file = UploadedFile::fake()->image('ktp.jpg', 800, 600)->size(512);

        $relativePath = $this->service->store($this->testUserId, $file, 'identity');

        $this->assertStringStartsWith("assets/reseller-documents/{$this->testUserId}/identity_", $relativePath);
        $this->assertStringEndsWith('.jpg', $relativePath);
        $this->assertFileExists(public_path($relativePath));
    }

    /**
     * Test store creates target directory if it does not exist.
     */
    public function test_store_creates_target_directory_when_missing(): void
    {
        $directory = public_path("assets/reseller-documents/{$this->testUserId}");
        $this->assertDirectoryDoesNotExist($directory);

        $file = UploadedFile::fake()->image('selfie.jpg', 800, 600)->size(512);

        $relativePath = $this->service->store($this->testUserId, $file, 'selfie');

        $this->assertDirectoryExists($directory);
        $this->assertFileExists(public_path($relativePath));
    }

    /**
     * Test delete removes existing file.
     */
    public function test_delete_removes_existing_file(): void
    {
        $relativePath = "assets/reseller-documents/{$this->testUserId}/old_file.jpg";
        $absolutePath = public_path($relativePath);

        File::ensureDirectoryExists(dirname($absolutePath));
        File::put($absolutePath, 'old file content');

        $this->assertFileExists($absolutePath);

        $this->service->delete($relativePath);

        $this->assertFileDoesNotExist($absolutePath);
    }

    /**
     * Test delete safely ignores blank path.
     */
    public function test_delete_ignores_blank_path(): void
    {
        $this->service->delete(null);
        $this->service->delete('');

        $this->assertTrue(true);
    }

    /**
     * Test replace deletes old file and stores new file.
     */
    public function test_replace_deletes_old_file_and_stores_new_file(): void
    {
        $oldRelativePath = "assets/reseller-documents/{$this->testUserId}/old_identity.jpg";
        $oldAbsolutePath = public_path($oldRelativePath);

        File::ensureDirectoryExists(dirname($oldAbsolutePath));
        File::put($oldAbsolutePath, 'old file content');

        $this->assertFileExists($oldAbsolutePath);

        $newFile = UploadedFile::fake()->image('new-ktp.png', 800, 600)->size(512);

        $newRelativePath = $this->service->replace($oldRelativePath, $this->testUserId, $newFile, 'identity');

        $this->assertFileDoesNotExist($oldAbsolutePath);
        $this->assertFileExists(public_path($newRelativePath));
        $this->assertStringEndsWith('.png', $newRelativePath);
    }

    /**
     * Test returned paths are normalized to forward slashes.
     */
    public function test_store_returns_path_with_forward_slashes(): void
    {
        $file = UploadedFile::fake()->image('business.jpg', 800, 600)->size(512);

        $relativePath = $this->service->store($this->testUserId, $file, 'business_proof');

        $this->assertStringNotContainsString('\\', $relativePath);
        $this->assertStringContainsString('/', $relativePath);
    }

    private function cleanupTestDirectory(): void
    {
        $directory = public_path("assets/reseller-documents/{$this->testUserId}");

        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
    }
}
