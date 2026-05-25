<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reseller_callback_profiles')) {
            return;
        }

        Schema::create('reseller_callback_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reseller_integration_id')
                ->constrained('reseller_integrations')
                ->cascadeOnDelete();

            $table->boolean('is_enabled')->default(false);
            $table->string('callback_url')->nullable();
            $table->text('webhook_secret_encrypted')->nullable();
            $table->string('signing_algorithm', 32)->default('sha256');
            $table->string('signature_header', 64)->default('X-Callback-Signature');
            $table->unsignedSmallInteger('version')->default(1);
            $table->json('ip_allowlist')->nullable();
            $table->boolean('retry_enabled')->default(true);
            $table->unsignedTinyInteger('max_retry')->default(3);
            $table->unsignedInteger('timeout_ms')->default(10000);
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_test_status', 32)->nullable();
            $table->text('last_test_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('reseller_integration_id', 'reseller_callback_profiles_unique_integration');
            $table->index(['is_enabled']);
            $table->index(['last_test_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_callback_profiles');
    }
};
