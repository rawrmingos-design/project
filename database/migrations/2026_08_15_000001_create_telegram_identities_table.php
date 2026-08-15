<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_identities')) {
            return;
        }

        Schema::create('telegram_identities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('bot_scope', 100);
            $table->string('telegram_user_id', 64);
            $table->string('chat_id', 64)->nullable();
            $table->string('username', 255)->nullable();
            $table->string('first_name', 255)->nullable();
            $table->string('last_name', 255)->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['bot_scope', 'telegram_user_id', 'revoked_at'], 'telegram_identity_lookup_idx');
            $table->index(['tenant_id', 'user_id', 'bot_scope', 'revoked_at'], 'telegram_identity_user_idx');
            $table->index(['user_id', 'revoked_at'], 'telegram_identity_active_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_identities');
    }
};
