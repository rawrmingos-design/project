<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reset_callback_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pembelian_id')->nullable()->constrained('pembelians')->nullOnDelete();
            $table->string('event_name');
            $table->string('order_id');
            $table->string('base_order_id');
            $table->string('display_order_id');
            $table->string('attempt_reference');
            $table->unsignedInteger('invoice_version')->default(0);
            $table->string('target_status');
            $table->string('callback_url');
            $table->string('signature_algorithm')->default('sha256');
            $table->string('idempotency_key')->unique();
            $table->json('payload');
            $table->unsignedInteger('attempt_count')->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('last_attempted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedSmallInteger('last_response_status')->nullable();
            $table->text('last_response_body')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable();
            $table->timestamps();

            $table->index(['pembelian_id', 'invoice_version']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reset_callback_deliveries');
    }
};
