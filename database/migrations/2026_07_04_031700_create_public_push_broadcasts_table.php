<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('public_push_broadcasts')) {
            return;
        }

        Schema::create('public_push_broadcasts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('send_mode', 24)->default('now');
            $table->string('status', 24)->default('queued')->index();
            $table->string('title', 120);
            $table->text('body');
            $table->string('target_url', 255);
            $table->json('payload')->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->json('failure_messages')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_push_broadcasts');
    }
};
