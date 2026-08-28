<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_notification_deliveries')) {
            return;
        }

        Schema::create('invoice_notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->string('order_id', 191)->index();
            $table->unsignedInteger('invoice_version')->default(0);
            $table->string('channel', 20);
            $table->string('transition', 50);
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider', 40)->nullable();
            $table->string('template_slug', 100);
            $table->text('recipient')->nullable();
            $table->string('recipient_hash', 64)->nullable()->index();
            $table->json('payload_json')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->text('last_error')->nullable();
            $table->string('provider_message_id', 191)->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);
            $table->index(['order_id', 'invoice_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_notification_deliveries');
    }
};
