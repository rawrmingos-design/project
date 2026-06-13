<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reseller_documents', function (Blueprint $table) {
            $table->id();
            
            // User relationship
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->comment('Owner of document');
            
            // Document classification
            $table->enum('document_type', [
                'identity',        // KTP/ID Card
                'selfie',          // Selfie with ID
                'business_proof'   // Screenshot toko/foto konter
            ])->comment('Jenis dokumen');
            
            // File information
            $table->string('file_path', 500)
                ->comment('Storage path: storage/app/reseller_documents/{user_id}/{filename}');
            
            $table->string('file_name', 255)
                ->comment('Original filename');
            
            $table->unsignedInteger('file_size')
                ->comment('File size in bytes');
            
            $table->string('mime_type', 100)
                ->comment('MIME type for security validation');
            
            // Verification status
            $table->enum('status', [
                'pending',   // Baru upload, belum direview
                'approved',  // Lolos verifikasi
                'rejected'   // Ditolak (blur, fake, dll)
            ])->default('pending');
            
            // Admin notes
            $table->text('notes')
                ->nullable()
                ->comment('Admin notes atau rejection reason');
            
            // Timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'document_type'], 'idx_user_doc_type');
            $table->index('status', 'idx_doc_status');
            
            // Unique constraint: 1 user can only have 1 file per document_type
            $table->unique(['user_id', 'document_type'], 'unique_user_document');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_documents');
    }
};
