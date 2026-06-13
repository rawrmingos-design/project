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
        Schema::create('reseller_application_reviews', function (Blueprint $table) {
            $table->id();
            
            // User being reviewed
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->comment('User whose application is being reviewed');
            
            // Action type
            $table->enum('action', [
                'submitted',    // User submit aplikasi
                'approved',     // Admin approve
                'rejected',     // Admin reject
                'resubmitted'   // User submit ulang after rejection
            ])->comment('Jenis aksi yang terjadi');
            
            // Admin who performed the action
            $table->unsignedBigInteger('reviewed_by')
                ->nullable()
                ->comment('NULL untuk user actions (submit), filled untuk admin actions');
            
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Review notes
            $table->text('notes')
                ->nullable()
                ->comment('Admin notes, rejection reason, atau internal comments');
            
            // Timestamp (no updated_at needed - immutable audit log)
            $table->timestamp('created_at')
                ->useCurrent()
                ->comment('Waktu aksi dilakukan');
            
            // Indexes
            $table->index(['user_id', 'created_at'], 'idx_user_timeline');
            $table->index('action', 'idx_action_type');
            // Note: reviewed_by already indexed by foreign key
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_application_reviews');
    }
};
