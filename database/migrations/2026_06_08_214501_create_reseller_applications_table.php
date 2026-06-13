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
        Schema::create('reseller_applications', function (Blueprint $table) {
            $table->id();
            
            // User relationship (one-to-one)
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->comment('Applicant user');
            
            // Application Status
            $table->enum('status', [
                'inactive',  // Default - not yet applied
                'pending',   // Under review
                'approved',  // Approved
                'rejected'   // Rejected
            ])
            ->default('inactive')
            ->comment('Current application status');
            
            // Lifecycle Timestamps
            $table->timestamp('applied_at')
                ->nullable()
                ->comment('When user submitted application');
            
            $table->timestamp('approved_at')
                ->nullable()
                ->comment('When admin approved');
            
            $table->timestamp('rejected_at')
                ->nullable()
                ->comment('When admin rejected');
            
            // Admin Tracking
            $table->unsignedBigInteger('reviewed_by')
                ->nullable()
                ->comment('Admin user_id who reviewed');
            
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Rejection Reason
            $table->text('rejection_reason')
                ->nullable()
                ->comment('Why application was rejected');
            
            // Business Information (JSON)
            $table->json('business_meta')
                ->nullable()
                ->comment('Business data: name, url, volume, reason');
            
            // Metadata
            $table->string('submitted_from_ip', 45)
                ->nullable()
                ->comment('IP address when submitted');
            
            // Standard timestamps
            $table->timestamps();
            
            // Indexes for performance
            $table->index('status', 'idx_application_status');
            $table->index(['status', 'applied_at'], 'idx_status_date');
            $table->index('reviewed_by', 'idx_reviewer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_applications');
    }
};
