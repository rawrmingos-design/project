<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reseller_push_subscriptions')) {
            return;
        }

        Schema::create('reseller_push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('endpoint', 191);
            $table->text('public_key');
            $table->string('auth_token', 255);
            $table->string('content_encoding', 32)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->unique('endpoint');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_push_subscriptions');
    }
};
