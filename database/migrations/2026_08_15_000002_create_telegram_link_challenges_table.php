<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('telegram_link_challenges')) {
            return;
        }

        Schema::create('telegram_link_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->string('bot_scope', 100);
            $table->string('token_hash', 255);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'bot_scope', 'expires_at'], 'telegram_challenge_user_idx');
            $table->index(['bot_scope', 'expires_at'], 'telegram_challenge_scope_idx');
            $table->index(['consumed_at', 'revoked_at'], 'telegram_challenge_lifecycle_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_link_challenges');
    }
};
