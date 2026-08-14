<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_link_challenges')) {
            return;
        }

        Schema::create('whatsapp_link_challenges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('whatsapp_number', 20);
            $table->string('code_hash', 255);
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_number', 'expires_at']);
            $table->index(['user_id', 'expires_at']);
            $table->index(['consumed_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_link_challenges');
    }
};
