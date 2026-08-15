<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bot_checkout_intents')) {
            return;
        }

        Schema::create('bot_checkout_intents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('intent_id')->unique();
            $table->string('tenant_scope', 64)->default('main');
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 40);
            $table->string('sender_fingerprint', 64);
            $table->string('origin_message_fingerprint', 64);
            $table->string('confirmation_message_fingerprint', 64)->nullable();
            $table->string('action_token_hash', 64)->unique();
            $table->text('action_token');
            $table->longText('payload');
            $table->string('payload_fingerprint', 64);
            $table->longText('quote_snapshot');
            $table->string('quote_fingerprint', 64);
            $table->string('status', 40)->default('awaiting_confirmation');
            $table->string('merchant_reference', 191)->nullable()->unique();
            $table->string('order_id', 191)->nullable()->index();
            $table->string('failure_code', 100)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('provider_dispatched_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['tenant_scope', 'source', 'sender_fingerprint', 'origin_message_fingerprint'],
                'bot_checkout_intents_origin_unique',
            );
            $table->index(['status', 'expires_at'], 'bot_checkout_intents_status_expiry_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_checkout_intents');
    }
};
