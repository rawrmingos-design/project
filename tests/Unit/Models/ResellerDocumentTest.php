<?php

namespace Tests\Unit\Models;

use App\Models\ResellerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerDocumentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that ResellerDocument has a user relationship.
     */
    public function test_has_user_relationship(): void
    {
        $user = User::factory()->create();
        $document = ResellerDocument::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(User::class, $document->user);
        $this->assertEquals($user->id, $document->user->id);
    }

    /**
     * Test that file path can be stored.
     */
    public function test_can_store_file_path(): void
    {
        $filePath = 'reseller-documents/test-document.jpg';
        
        $document = ResellerDocument::factory()->create([
            'file_path' => $filePath,
        ]);

        $this->assertEquals($filePath, $document->file_path);
        $this->assertDatabaseHas('reseller_documents', [
            'id' => $document->id,
            'file_path' => $filePath,
        ]);
    }

    /**
     * Test that file metadata can be stored.
     */
    public function test_can_store_file_metadata(): void
    {
        $document = ResellerDocument::factory()->create([
            'file_name' => 'test-ktp.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 1024000, // 1MB
        ]);

        $this->assertEquals('test-ktp.jpg', $document->file_name);
        $this->assertEquals('image/jpeg', $document->mime_type);
        $this->assertEquals(1024000, $document->file_size);
    }

    /**
     * Test document type enum values.
     */
    public function test_document_type_enum_values(): void
    {
        // Test identity type
        $identity = ResellerDocument::factory()->identity()->create();
        $this->assertEquals('identity', $identity->document_type);

        // Test selfie type
        $selfie = ResellerDocument::factory()->selfie()->create();
        $this->assertEquals('selfie', $selfie->document_type);

        // Test business_proof type
        $businessProof = ResellerDocument::factory()->businessProof()->create();
        $this->assertEquals('business_proof', $businessProof->document_type);
    }

    /**
     * Test file URL accessor.
     */
    public function test_file_url_accessor_returns_asset_path(): void
    {
        $document = ResellerDocument::factory()->create([
            'file_path' => 'reseller-documents/test.jpg',
        ]);

        $expectedUrl = asset('reseller-documents/test.jpg');
        $this->assertEquals($expectedUrl, $document->file_url);
    }

    /**
     * Test formatted size accessor for megabytes.
     */
    public function test_formatted_size_accessor_returns_megabytes(): void
    {
        $document = ResellerDocument::factory()->create([
            'file_size' => 1572864, // 1.5 MB
        ]);

        $this->assertEquals('1.50 MB', $document->formatted_size);
    }

    /**
     * Test formatted size accessor for kilobytes.
     */
    public function test_formatted_size_accessor_returns_kilobytes(): void
    {
        $document = ResellerDocument::factory()->create([
            'file_size' => 512000, // 500 KB
        ]);

        $this->assertEquals('500.00 KB', $document->formatted_size);
    }

    /**
     * Test formatted size accessor for bytes.
     */
    public function test_formatted_size_accessor_returns_bytes(): void
    {
        $document = ResellerDocument::factory()->create([
            'file_size' => 512, // < 1KB
        ]);

        $this->assertEquals('512 bytes', $document->formatted_size);
    }

    /**
     * Test type label accessor for different document types.
     */
    public function test_type_label_accessor_returns_correct_labels(): void
    {
        $identity = ResellerDocument::factory()->identity()->create();
        $this->assertEquals('KTP/ID Card', $identity->type_label);

        $selfie = ResellerDocument::factory()->selfie()->create();
        $this->assertEquals('Selfie with ID', $selfie->type_label);

        $businessProof = ResellerDocument::factory()->businessProof()->create();
        $this->assertEquals('Business Proof', $businessProof->type_label);
    }

    /**
     * Test isImage method returns true for image mime types.
     */
    public function test_is_image_returns_true_for_image_mime_type(): void
    {
        $document = ResellerDocument::factory()->create([
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertTrue($document->isImage());
    }

    /**
     * Test isImage method returns false for non-image mime types.
     */
    public function test_is_image_returns_false_for_non_image_mime_type(): void
    {
        $document = ResellerDocument::factory()->pdf()->create();

        $this->assertFalse($document->isImage());
    }

    /**
     * Test isPdf method returns true for PDF mime type.
     */
    public function test_is_pdf_returns_true_for_pdf_mime_type(): void
    {
        $document = ResellerDocument::factory()->pdf()->create();

        $this->assertTrue($document->isPdf());
    }

    /**
     * Test isPdf method returns false for non-PDF mime types.
     */
    public function test_is_pdf_returns_false_for_non_pdf_mime_type(): void
    {
        $document = ResellerDocument::factory()->create([
            'mime_type' => 'image/jpeg',
        ]);

        $this->assertFalse($document->isPdf());
    }

    /**
     * Test isPending method returns true for pending status.
     */
    public function test_is_pending_returns_true_for_pending_status(): void
    {
        $document = ResellerDocument::factory()->create([
            'status' => 'pending',
        ]);

        $this->assertTrue($document->isPending());
    }

    /**
     * Test isApproved method returns true for approved status.
     */
    public function test_is_approved_returns_true_for_approved_status(): void
    {
        $document = ResellerDocument::factory()->approved()->create();

        $this->assertTrue($document->isApproved());
    }

    /**
     * Test isRejected method returns true for rejected status.
     */
    public function test_is_rejected_returns_true_for_rejected_status(): void
    {
        $document = ResellerDocument::factory()->rejected()->create();

        $this->assertTrue($document->isRejected());
    }

    /**
     * Test pending scope filters pending documents.
     */
    public function test_pending_scope_filters_pending_documents(): void
    {
        ResellerDocument::factory()->create(['status' => 'pending']);
        ResellerDocument::factory()->approved()->create();
        ResellerDocument::factory()->rejected()->create();

        $pendingDocs = ResellerDocument::pending()->get();

        $this->assertCount(1, $pendingDocs);
        $this->assertEquals('pending', $pendingDocs->first()->status);
    }

    /**
     * Test approved scope filters approved documents.
     */
    public function test_approved_scope_filters_approved_documents(): void
    {
        ResellerDocument::factory()->create(['status' => 'pending']);
        ResellerDocument::factory()->approved()->create();
        ResellerDocument::factory()->rejected()->create();

        $approvedDocs = ResellerDocument::approved()->get();

        $this->assertCount(1, $approvedDocs);
        $this->assertEquals('approved', $approvedDocs->first()->status);
    }

    /**
     * Test rejected scope filters rejected documents.
     */
    public function test_rejected_scope_filters_rejected_documents(): void
    {
        ResellerDocument::factory()->create(['status' => 'pending']);
        ResellerDocument::factory()->approved()->create();
        ResellerDocument::factory()->rejected()->create();

        $rejectedDocs = ResellerDocument::rejected()->get();

        $this->assertCount(1, $rejectedDocs);
        $this->assertEquals('rejected', $rejectedDocs->first()->status);
    }
}
