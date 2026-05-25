<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reseller_integrations')) {
            return;
        }

        Schema::create('reseller_integrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('integration_type', 32);
            $table->string('integration_code', 64);
            $table->string('mode', 16)->default('live');
            $table->string('credential_source', 16)->default('global');
            $table->boolean('is_active')->default(true);
            $table->string('health_status', 32)->nullable();
            $table->timestamp('last_health_checked_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'integration_type', 'integration_code', 'mode'], 'reseller_integrations_unique_scope');
            $table->index(['integration_type', 'integration_code']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reseller_integrations');
    }
};
